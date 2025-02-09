<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\GenerateRequestId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Validator;
use Yaza\LaravelGoogleDriveStorage\Gdrive;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\Permission;

class NewsController
{
    use ApiResponse, GenerateRequestId;

    public function index()
    {
        try {
            $cached = Redis::get('news.index');

            if ($cached) {
                $news = json_decode($cached);
                return $this->sendResponse($news, 'news fetched successfully from cache');
            }

            $newsData = News::with('user', 'category', 'tags')
                // ->where('is_published', true)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($news) => [
                    'id' => $news->id,
                    'title' => Str::limit($news->title, 20),
                    'slug' => $news->slug,
                    'image_url' => $news->image_url ?? null,
                    'content' => Str::limit($news->content, 50),
                    'is_published' => $news->is_published ? 'Published' : 'Draft',
                    'published_at' => $news->published_at ?? null,
                    'author' => $news->user->name,
                    'category' => $news->category->category ?? null,
                    'tags' => $news->tags->pluck('tag')->toArray(),
                ]);

            if ($newsData->isEmpty()) {
                return $this->sendResponse([], 'No news found');
            }

            Redis::setex('news.index', 3600, json_encode($newsData));

            return $this->sendResponse($newsData, 'news fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching news');
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                'title' => 'required|string',
                'content' => 'required|string',
                'category_id' => 'required|string|exists:categories,id',
                'tags' => 'nullable|array',
                'tags.*' => 'exists:tags,id',
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();
                $filePath = env('GOOGLE_DRIVE_SUBFOLDER') . '/news/images/' . $fileName;

                Gdrive::put($filePath, $image);

                $fileMetadata = collect(Gdrive::all(env('GOOGLE_DRIVE_SUBFOLDER') . '/news/images/'))
                    ->firstWhere('extra_metadata.name', $fileName);

                if (!$fileMetadata) {
                    throw new \Exception("The metadata file was not found in Google Drive.");
                }

                // Setup Google Client untuk mengubah permission
                $client = new Client();
                $client->setClientId(config('filesystems.disks.google.clientId'));
                $client->setClientSecret(config('filesystems.disks.google.clientSecret'));
                $client->refreshToken(config('filesystems.disks.google.refreshToken'));

                $service = new Drive($client);

                // Buat permission baru (public)
                $permission = new Permission();
                $permission->setRole('reader');
                $permission->setType('anyone');

                // Terapkan permission ke file
                $service->permissions->create(
                    $fileMetadata['extra_metadata']['id'],
                    $permission,
                    ['fields' => 'id']
                );

                $thumbnailUrl = env('GOOGLE_DRIVE_URL') . $fileMetadata['extra_metadata']['id'];
            }

            $news = News::create([
                'title' => $request->title,
                'image_url' => $thumbnailUrl ?? null,
                'image_name' => $fileName ?? null,
                'content' => $request->content,
                'user_id' => $request->user()->id,
                'category_id' => $request->category_id,
                'is_published' => true,
                'published_at' => now(),
            ]);

            if ($request->tags) {
                $news->tags()->attach($request->tags);
            }

            $news = News::with(['user', 'category', 'tags'])->findOrFail($news->id);

            $data = [
                'id' => $news->id,
                'title' => Str::limit($news->title, 20),
                'slug' => $news->slug,
                'image_url' => $news->image_url ?? null,
                'content' => Str::limit($news->content, 50),
                'is_published' => $news->is_published ? 'Published' : 'Draft',
                'published_at' => $news->published_at ?? null,
                'author' => $news->user->name,
                'category' => $news->category->category ?? null,
                'tags' => $news->tags->pluck('tag')->toArray(),
            ];

            Activity::all()->last();

            Redis::del('news.index');
            Redis::del('news.draft');

            DB::commit();

            return $this->sendResponse($data, 'News created successfully', 201);
        } catch (\Exception $e) {
            DB::rollback();
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during creating news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while creating news');
        }
    }

    public function show($id)
    {
        try {
            $news = News::with('user', 'category', 'tags')->find($id);

            if (!$news) {
                return $this->sendError('News not found', 404);
            }

            $data = [
                'id' => $news->id,
                'title' => $news->title,
                'slug' => $news->slug,
                'image_url' => $news->image_url ?? null,
                'content' => $news->content ?? null,
                'is_published' => $news->is_published ? 'Published' : 'Draft',
                'published_at' => $news->published_at ?? null,
                'author' => $news->user->name,
                'category' => $news->category->category ?? null,
                'tags' => $news->tags->pluck('tag')->toArray(),
            ];

            return $this->sendResponse($data, 'News fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during showing news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while showing news');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $news = News::with('user', 'category', 'tags')->find($id);

            if (!$news) {
                return $this->sendError('News not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'title' => 'required|string',
                'content' => 'required|string',
                'category_id' => 'required|string|exists:categories,id',
                'tags' => 'nullable|array',
                'tags.*' => 'exists:tags,id',
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $newFileName = Str::uuid()->toString() . '.' . $image->getClientOriginalExtension();
                $newFilePath = env('GOOGLE_DRIVE_SUBFOLDER') . '/news/images/' . $newFileName;

                Gdrive::put($newFilePath, $image);

                $files = Gdrive::all(env('GOOGLE_DRIVE_SUBFOLDER') . '/news/images/');
                $filesCollection = collect($files);

                if ($news->image_name) {
                    $oldFile = $filesCollection->firstWhere('extra_metadata.name', $news->image_name);
                    if ($oldFile) {
                        try {
                            Gdrive::delete(env('GOOGLE_DRIVE_SUBFOLDER') . '/news/images/' . $news->image_name);
                        } catch (\Exception $e) {
                            Log::warning("Failed to delete old file: {$news->image_name}");
                        }
                    }
                }

                $newFileMetadata = $filesCollection->firstWhere('extra_metadata.name', $newFileName);
                if (!$newFileMetadata) {
                    throw new \Exception("Failed to get the metadata of the newly uploaded file.");
                }

                // Setup Google Client untuk mengubah permission
                $client = new Client();
                $client->setClientId(config('filesystems.disks.google.clientId'));
                $client->setClientSecret(config('filesystems.disks.google.clientSecret'));
                $client->refreshToken(config('filesystems.disks.google.refreshToken'));

                $service = new Drive($client);

                // Buat permission baru (public)
                $permission = new Permission();
                $permission->setRole('reader');
                $permission->setType('anyone');

                // Terapkan permission ke file
                $service->permissions->create(
                    $newFileMetadata['extra_metadata']['id'],
                    $permission,
                    ['fields' => 'id']
                );

                $news->image_url = env('GOOGLE_DRIVE_URL') . $newFileMetadata['extra_metadata']['id'];
                $news->image_name = $newFileName;
            }

            $news->title = $request->title;
            $news->content = $request->content;
            $news->category_id = $request->category_id;

            $news->save();

            if ($request->has('tags')) {
                $news->tags()->sync($request->tags);
            } else {
                $news->tags()->sync([]);
            }

            $news->refresh();

            $data = [
                'id' => $news->id,
                'title' => $news->title,
                'slug' => $news->slug,
                'image_url' => $news->image_url ?? null,
                'content' => $news->content ?? null,
                'is_published' => $news->is_published ? 'Published' : 'Draft',
                'published_at' => $news->published_at ?? null,
                'author' => $news->user->name,
                'category' => $news->category->category ?? null,
                'tags' => $news->tags->pluck('tag')->toArray(),
            ];

            Activity::all()->last();

            Redis::del('news.index');
            Redis::del('news.draft');

            DB::commit();

            return $this->sendResponse($data, 'News updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during updating news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while updating news');
        }
    }

    public function destroy($id)
    {
        try {
            $news = News::with('user', 'category', 'tags')->find($id);

            if (!$news) {
                return $this->sendError('News not found', 404);
            }

            $news->delete();

            $data = [
                'id' => $news->id,
                'title' => $news->title,
                'slug' => $news->slug,
                'image_url' => $news->image_url ?? null,
                'content' => $news->content ?? null,
                'is_published' => $news->is_published ? 'Published' : 'Draft',
                'published_at' => $news->published_at ?? null,
                'author' => $news->user->name,
                'category' => $news->category->category ?? null,
                'tags' => $news->tags->pluck('tag')->toArray(),
            ];

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('news.index');
                $pipe->del('news.trashed');
            });

            return $this->sendResponse($data, 'News deleted successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during deleting news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while deleting news');
        }
    }

    public function trashed()
    {
        try {
            $cached = Redis::get('news.trashed');

            if ($cached) {
                $news = json_decode($cached);
                return $this->sendResponse($news, 'news fetched successfully from cache');
            }

            $news = News::onlyTrashed()->with('user', 'category', 'tags')->orderBy('published_at', 'desc')->get();

            if ($news->isEmpty()) {
                return $this->sendResponse([], 'No news found');
            }

            $data = $news->map(function ($news) {
                return [
                    'id' => $news->id,
                    'title' => $news->title,
                    'slug' => $news->slug,
                    'image_url' => $news->image_url ?? null,
                    'content' => $news->content ?? null,
                    'is_published' => $news->is_published ? 'Published' : 'Draft',
                    'published_at' => $news->published_at ?? null,
                    'author' => $news->user->name,
                    'category' => $news->category->category ?? null,
                    'tags' => $news->tags->pluck('tag')->toArray(),
                ];
            });

            Redis::setex('news.trashed', 3600, json_encode($data));

            return $this->sendResponse($data, 'news fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching trashed news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching trashed news');
        }
    }

    public function restore($id)
    {
        try {
            $news = News::onlyTrashed()->with('user', 'category', 'tags')->find($id);

            if (!$news) {
                return $this->sendError('News not found', 404);
            }

            $news->restore();

            $data = [
                'id' => $news->id,
                'title' => $news->title,
                'slug' => $news->slug,
                'image_url' => $news->image_url ?? null,
                'content' => $news->content ?? null,
                'is_published' => $news->is_published ? 'Published' : 'Draft',
                'published_at' => $news->published_at ?? null,
                'author' => $news->user->name,
                'category' => $news->category->category ?? null,
                'tags' => $news->tags->pluck('tag')->toArray(),
            ];

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('news.index');
                $pipe->del('news.trashed');
                $pipe->del('news.draft');
            });

            return $this->sendResponse($data, 'News restored successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during restoring news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while restoring news');
        }
    }

    public function forceDelete($id)
    {
        try {
            $news = News::onlyTrashed()->where('id', $id)->first();

            if (!$news) {
                return $this->sendError('News not found', 404);
            }

            DB::beginTransaction();

            if ($news->image_name) {
                Gdrive::delete(env('GOOGLE_DRIVE_SUBFOLDER') . '/news/images/' . $news->image_name);
            }

            $news->forceDelete();

            $news->tags()->sync([]);

            $news->refresh();

            $data = [
                'id' => $news->id,
                'title' => $news->title,
                'slug' => $news->slug,
                'image_url' => $news->image_url ?? null,
                'content' => $news->content ?? null,
                'is_published' => $news->is_published ? 'Published' : 'Draft',
                'published_at' => $news->published_at ?? null,
                'author' => $news->user->name,
                'category' => $news->category->category ?? null,
                'tags' => $news->tags->pluck('tag')->toArray(),
            ];

            Activity::all()->last();

            Redis::del('news.trashed');

            DB::commit();

            return $this->sendResponse($data, 'News deleted permanently');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during force deleting news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while force deleting news');
        }
    }

    public function search(Request $request)
    {
        try {
            if (!$request->has('q') || empty($request->q)) {
                $cached = Redis::get('news.index');

                if ($cached) {
                    $news = json_decode($cached);
                    return $this->sendResponse($news, 'News fetched successfully from cache');
                }

                $news = News::with('user', 'category', 'tags')->get();

                if ($news->isEmpty()) {
                    return $this->sendResponse([], 'No news found');
                }

                $news = $news->map(function ($news) {
                    return [
                        'id' => $news->id,
                        'title' => Str::limit($news->title, 20),
                        'slug' => $news->slug,
                        'image_url' => $news->image_url ?? null,
                        'content' => Str::limit($news->content, 50),
                        'is_published' => $news->is_published ? 'Published' : 'Draft',
                        'published_at' => $news->published_at ?? null,
                        'author' => $news->user->name,
                        'category' => $news->category->category ?? null,
                        'tags' => $news->tags->pluck('tag')->toArray(),
                    ];
                });

                Redis::setex('news.index', 3600, json_encode($news));

                return $this->sendResponse($news, 'News fetched successfully');
            }

            $news = News::with('user', 'category', 'tags')
                ->where('title', 'like', '%' . $request->q . '%')
                ->get();

            if ($news->isEmpty()) {
                return $this->sendResponse([], 'No news found matching your query');
            }

            $news = $news->map(function ($news) {
                return [
                    'id' => $news->id,
                    'title' => Str::limit($news->title, 20),
                    'slug' => $news->slug,
                    'image_url' => $news->image_url ?? null,
                    'content' => Str::limit($news->content, 50),
                    'is_published' => $news->is_published ? 'Published' : 'Draft',
                    'published_at' => $news->published_at ?? null,
                    'author' => $news->user->name,
                    'category' => $news->category->category ?? null,
                    'tags' => $news->tags->pluck('tag')->toArray(),
                ];
            });

            return $this->sendResponse($news, 'News fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during searching news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while searching news');
        }
    }

    public function draftNews(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'title' => 'required|string',
                'content' => 'nullable|string',
                'category_id' => 'nullable|string|exists:categories,id',
                'tags' => 'nullable|array',
                'tags.*' => 'exists:tags,id',
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();
                $filePath = env('GOOGLE_DRIVE_SUBFOLDER') . '/news/images/' . $fileName;

                Gdrive::put($filePath, $image);

                $fileMetadata = collect(Gdrive::all(env('GOOGLE_DRIVE_SUBFOLDER') . '/news/images/'))
                    ->firstWhere('extra_metadata.name', $fileName);

                if (!$fileMetadata) {
                    throw new \Exception("The metadata file was not found in Google Drive.");
                }

                // Setup Google Client untuk mengubah permission
                $client = new Client();
                $client->setClientId(config('filesystems.disks.google.clientId'));
                $client->setClientSecret(config('filesystems.disks.google.clientSecret'));
                $client->refreshToken(config('filesystems.disks.google.refreshToken'));

                $service = new Drive($client);

                // Buat permission baru (public)
                $permission = new Permission();
                $permission->setRole('reader');
                $permission->setType('anyone');

                // Terapkan permission ke file
                $service->permissions->create(
                    $fileMetadata['extra_metadata']['id'],
                    $permission,
                    ['fields' => 'id']
                );

                $thumbnailUrl = env('GOOGLE_DRIVE_URL') . $fileMetadata['extra_metadata']['id'];
            }

            $news = News::create([
                'title' => $request->title,
                'image_url' => $thumbnailUrl ?? null,
                'image_name' => $fileName ?? null,
                'content' => $request->content ?? null,
                'user_id' => $request->user()->id,
                'category_id' => $request->category_id ?? null,
                'is_published' => false,
                'published_at' => null,
            ]);

            if ($request->tags) {
                $news->tags()->attach($request->tags);
            }

            $news = News::with(['user', 'category', 'tags'])->findOrFail($news->id);

            $data = [
                'id' => $news->id,
                'title' => Str::limit($news->title, 20),
                'slug' => $news->slug,
                'image_url' => $news->image_url ?? null,
                'content' => Str::limit($news->content, 50),
                'is_published' => $news->is_published ? 'Published' : 'Draft',
                'published_at' => $news->published_at ?? null,
                'author' => $news->user->name,
                'category' => $news->category->category ?? null,
                'tags' => $news->tags->pluck('tag')->toArray(),
            ];

            Activity::all()->last();

            Redis::del('news.index');
            Redis::del('news.draft');

            DB::commit();

            return $this->sendResponse($data, 'News created successfully', 201);
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during drafting news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while drafting news');
        }
    }

    public function showDraftNews()
    {
        try {
            $cached = Redis::get('news.draft');

            if ($cached) {
                $news = json_decode($cached);
                return $this->sendResponse($news, 'news fetched successfully from cache');
            }

            $newsData = News::with('user', 'category', 'tags')
                ->where('is_published', false)
                ->get()
                ->map(fn($news) => [
                    'id' => $news->id,
                    'title' => Str::limit($news->title, 20),
                    'slug' => $news->slug,
                    'image_url' => $news->image_url ?? null,
                    'content' => Str::limit($news->content, 50),
                    'is_published' => $news->is_published ? 'Draft' : 'Published',
                    'published_at' => $news->published_at ?? null,
                    'author' => $news->user->name,
                    'category' => $news->category->category ?? null,
                    'tags' => $news->tags->pluck('tag')->toArray(),
                ]);

            if ($newsData->isEmpty()) {
                return $this->sendResponse([], 'No news found');
            }

            Redis::setex('news.draft', 3600, json_encode($newsData));

            return $this->sendResponse($newsData, 'news fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching drafted news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching drafted news');
        }
    }

    public function published(Request $request, $id)
    {
        try {
            $news = News::with('user', 'category', 'tags')
                ->where('is_published', false)
                ->find($id);

            if (!$news) {
                return $this->sendError('News not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                'title' => 'required|string',
                'content' => 'required|string',
                'category_id' => 'required|string|exists:categories,id',
                'tags' => 'nullable|array',
                'tags.*' => 'exists:tags,id',
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $newFileName = Str::uuid()->toString() . '.' . $image->getClientOriginalExtension();
                $newFilePath = env('GOOGLE_DRIVE_SUBFOLDER') . '/news/images/' . $newFileName;

                Gdrive::put($newFilePath, $image);

                $files = Gdrive::all(env('GOOGLE_DRIVE_SUBFOLDER') . '/news/images/');
                $filesCollection = collect($files);

                if ($news->image_name) {
                    $oldFile = $filesCollection->firstWhere('extra_metadata.name', $news->image_name);
                    if ($oldFile) {
                        try {
                            Gdrive::delete(env('GOOGLE_DRIVE_SUBFOLDER') . '/news/images/' . $news->image_name);
                        } catch (\Exception $e) {
                            Log::warning("Failed to delete old file: {$news->image_name}");
                        }
                    }
                }

                $newFileMetadata = $filesCollection->firstWhere('extra_metadata.name', $newFileName);
                if (!$newFileMetadata) {
                    throw new \Exception("Failed to get the metadata of the newly uploaded file.");
                }

                // Setup Google Client untuk mengubah permission
                $client = new Client();
                $client->setClientId(config('filesystems.disks.google.clientId'));
                $client->setClientSecret(config('filesystems.disks.google.clientSecret'));
                $client->refreshToken(config('filesystems.disks.google.refreshToken'));

                $service = new Drive($client);

                // Buat permission baru (public)
                $permission = new Permission();
                $permission->setRole('reader');
                $permission->setType('anyone');

                // Terapkan permission ke file
                $service->permissions->create(
                    $newFileMetadata['extra_metadata']['id'],
                    $permission,
                    ['fields' => 'id']
                );

                $news->image_url = env('GOOGLE_DRIVE_URL') . $newFileMetadata['extra_metadata']['id'];
                $news->image_name = $newFileName;
            }

            $news->title = $request->title;
            $news->content = $request->content;
            $news->category_id = $request->category_id;
            $news->is_published = true;
            $news->published_at = now();

            $news->save();

            if ($request->has('tags')) {
                $news->tags()->sync($request->tags);
            } else {
                $news->tags()->sync([]);
            }

            $news->refresh();

            $data = [
                'id' => $news->id,
                'title' => $news->title,
                'slug' => $news->slug,
                'image_url' => $news->image_url ?? null,
                'content' => $news->content ?? null,
                'is_published' => $news->is_published ? 'Published' : 'Draft',
                'published_at' => $news->published_at ?? null,
                'author' => $news->user->name,
                'category' => $news->category->category ?? null,
                'tags' => $news->tags->pluck('tag')->toArray(),
            ];

            Activity::all()->last();

            Redis::del('news.index');
            Redis::del('news.draft');

            DB::commit();

            return $this->sendResponse($data, 'News updated successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during publishing news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while publishing news');
        }
    }

    public function showByAuthor($slug)
    {
        try {
            $news = News::with('user', 'category', 'tags')
                ->where('is_published', true)
                ->whereHas('user', fn($query) => $query->where('slug', $slug))
                ->orderBy('published_at', 'desc')
                ->get()
                ->map(fn($news) => [
                    'id' => $news->id,
                    'title' => Str::limit($news->title, 20),
                    'slug' => $news->slug,
                    'image_url' => $news->image_url ?? null,
                    'content' => Str::limit($news->content, 50),
                    'is_published' => $news->is_published ? 'Published' : 'Draft',
                    'published_at' => $news->published_at ?? null,
                    'author' => $news->user->name,
                    'category' => $news->category->category ?? null,
                    'tags' => $news->tags->pluck('tag')->toArray(),
                ]);

            if ($news->isEmpty()) {
                return $this->sendError('News by author ' . $slug . ' not found', 404);
            }

            return $this->sendResponse($news, 'News by author ' . $slug . ' fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching news by author: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching news by author');
        }
    }

    public function showByCategory($slug)
    {
        try {
            $news = News::with('user', 'category', 'tags')
                ->where('is_published', true)
                ->whereHas('category', fn($query) => $query->where('slug', $slug))
                ->orderBy('published_at', 'desc')
                ->get()
                ->map(fn($news) => [
                    'id' => $news->id,
                    'title' => Str::limit($news->title, 20),
                    'slug' => $news->slug,
                    'image_url' => $news->image_url ?? null,
                    'content' => Str::limit($news->content, 50),
                    'is_published' => $news->is_published ? 'Published' : 'Draft',
                    'published_at' => $news->published_at ?? null,
                    'author' => $news->user->name,
                    'category' => $news->category->category ?? null,
                    'tags' => $news->tags->pluck('tag')->toArray(),
                ]);

            if ($news->isEmpty()) {
                return $this->sendError('News by category ' . $slug . ' not found', 404);
            }

            return $this->sendResponse($news, 'News by category ' . $slug . ' fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching news by category: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching news by category');
        }
    }

    public function showByTag($slug)
    {
        try {
            $news = News::with('user', 'category', 'tags')
                ->where('is_published', true)
                ->whereHas('tags', fn($query) => $query->where('slug', $slug))
                ->orderBy('published_at', 'desc')
                ->get()
                ->map(fn($news) => [
                    'id' => $news->id,
                    'title' => Str::limit($news->title, 20),
                    'slug' => $news->slug,
                    'image_url' => $news->image_url ?? null,
                    'content' => Str::limit($news->content, 50),
                    'is_published' => $news->is_published ? 'Published' : 'Draft',
                    'published_at' => $news->published_at ?? null,
                    'author' => $news->user->name,
                    'category' => $news->category->category ?? null,
                    'tags' => $news->tags->pluck('tag')->toArray(),
                ]);

            if ($news->isEmpty()) {
                return $this->sendError('News by tag ' . $slug . ' not found', 404);
            }

            return $this->sendResponse($news, 'News by tag ' . $slug . ' fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching news by tag: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching news by tag');
        }
    }

    public function singleNews($slug)
    {
        try {
            $news = News::with('user', 'category', 'tags')
                ->where('is_published', true)
                ->where('slug', $slug)
                ->get()
                ->map(fn($news) => [
                    'id' => $news->id,
                    'title' => $news->title,
                    'slug' => $news->slug,
                    'image_url' => $news->image_url ?? null,
                    'content' => $news->content ?? null,
                    'is_published' => $news->is_published ? 'Published' : 'Draft',
                    'published_at' => $news->published_at ?? null,
                    'author' => $news->user->name,
                    'category' => $news->category->category ?? null,
                    'tags' => $news->tags->pluck('tag')->toArray(),
                ]);

            if (!$news) {
                return $this->sendError('News not found', 404);
            }

            return $this->sendResponse($news, 'News fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching news');
        }
    }
}
