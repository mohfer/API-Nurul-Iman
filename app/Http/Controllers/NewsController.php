<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\NewsTag;
use App\Models\Category;
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
                ->select(['id', 'title', 'slug', 'image_url', 'content', 'user_id', 'category_id', 'is_published', 'published_at'])
                ->get()
                ->map(fn($news) => [
                    'id' => $news->id,
                    'title' => Str::limit($news->title, 20),
                    'slug' => $news->slug,
                    'image_url' => $news->image_url,
                    'content' => Str::limit($news->content, 50),
                    'is_published' => $news->is_published,
                    'published_at' => $news->published_at,
                    'author' => $news->user->name,
                    'category' => $news->category->category,
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
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
                'title' => 'required|string',
                'content' => 'required|string',
                'category_id' => 'required|string',
                'tags' => 'nullable|array',
                'tags.*' => 'exists:tags,id',
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            DB::beginTransaction();

            $image = $request->file('image');
            $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();
            $filePath = env('GOOGLE_DRIVE_SUBFOLDER') . '/news/images/' . $fileName;

            Gdrive::put($filePath, $image);

            $fileMetadata = collect(Gdrive::all(env('GOOGLE_DRIVE_SUBFOLDER') . '/news/images/'))
                ->firstWhere('extra_metadata.name', $fileName);

            if (!$fileMetadata) {
                throw new \Exception("The metadata file was not found in Google Drive.");
            }

            $thumbnailUrl = env('GOOGLE_DRIVE_URL') . $fileMetadata['extra_metadata']['id'];

            $news = News::create([
                'title' => $request->title,
                'image_url' => $thumbnailUrl,
                'image_name' => $fileName,
                'content' => $request->content,
                'user_id' => $request->user()->id,
                'category_id' => $request->category_id,
                'is_published' => true,
                'published_at' => now()
            ]);

            foreach ($request->tags as $tagId) {
                NewsTag::create([
                    'news_id' => $news->id,
                    'tag_id' => $tagId
                ]);
            }

            $data = [
                'id' => $news->id,
                'title' => Str::limit($news->title, 20),
                'slug' => $news->slug,
                'image_url' => $news->image_url,
                'content' => Str::limit($news->content, 50),
                'is_published' => $news->is_published,
                'published_at' => $news->published_at,
                'author' => $news->user->name,
                'category' => $news->category->category,
                'tags' => $news->tags->pluck('tag')->toArray(),
            ];

            Activity::all()->last();

            Redis::del('news.index');

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
            $news = News::find($id);

            if (!$news) {
                return $this->sendError('News not found', 404);
            }

            $data = [
                'id' => $news->id,
                'title' => $news->title,
                'slug' => $news->slug,
                'image_url' => $news->image_url,
                'content' => $news->content,
                'is_published' => $news->is_published,
                'published_at' => $news->published_at,
                'author' => $news->user->name,
                'category' => $news->category->category,
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
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during updating news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while updating news');
        }
    }

    public function destroy($id)
    {
        try {
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during deleting news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while deleting news');
        }
    }

    public function trashed()
    {
        try {
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching trashed news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching trashed news');
        }
    }

    public function restore($id)
    {
        try {
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during restoring news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while restoring news');
        }
    }

    public function forceDelete($id)
    {
        try {
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during force deleting news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while force deleting news');
        }
    }

    public function draftNews(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
                'content' => 'required|string',
                'user_id' => 'required|string',
                'category_id' => 'required|string',
                'is_published' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            $news = News::create([
                'title' => $request->title,
                'image_url' => $request->image,
                'image_name' => $request->image->getClientOriginalName(),
                'content' => $request->content,
                'user_id' => $request->user()->id,
                'category_id' => $request->category_id,
                'is_published' => false
            ]);

            $data = [
                'id' => $news->id,
                'title' => $news->title,
                'slug' =>  $news->slug,
                'thumbnail' => $news->thumbnail,
                'content' => $news->content,
                'user_id' => $news->user()->id,
                'category_id' => $news->category_id,
                'is_published' => false
            ];

            Activity::all()->last();

            return $this->sendResponse($data, 'News drafted successfully', 201);
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during drafting news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while drafting news');
        }
    }

    public function showDraftNews()
    {
        try {
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching drafted news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching drafted news');
        }
    }

    public function published(Request $request, $id)
    {
        try {
            $news = News::find($id);

            if (!$news) {
                return $this->sendError('News not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'thumbnail' => 'required|image',
                'content' => 'required|string',
                'category_id' => 'required|string',
                'is_published' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            $news->slug = null;
            $news->title = $request->title;
            $news->thumbnail = $request->thumbnail;
            $news->content = $request->content;
            $news->category_id = $request->category_id;
            $news->is_published = true;
            $news->published_at = date(now());
            $news->save();

            $data = [
                'is_published' => $news->is_published
            ];

            Activity::all()->last();

            return $this->sendResponse($data, 'News successfully published');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during publishing news: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while publishing news');
        }
    }

    public function showByAuthor(Request $request)
    {
        try {
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching news by author: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching news by author');
        }
    }

    public function showByCategory(Request $request)
    {
        try {
            $category = Category::with(['news.news_tags.tag', 'news.user', 'news.category'])->where('slug', $request->slug)->first();
            $news = $category->news;
            $newsTags = $news->pluck('news_tags')->flatten();
            $newsDetails = $news->where('is_published', true);

            if (!$newsDetails) {
                return $this->sendError('News with category ' . $category->name . ' not found', 404);
            }

            $tags = [];
            foreach ($newsTags as $newsTag) {
                $tags[] = [
                    'id' => $newsTag->tag->id,
                    'tag' => $newsTag->tag->tag,
                    'slug' => $newsTag->tag->slug
                ];
            }

            $data = [
                'id' => $newsDetails->id,
                'title' => $newsDetails->title,
                'thumbnail' => $newsDetails->thumbnail,
                'content' => $newsDetails->content,
                'author' => $newsDetails->user->name,
                'category' => $newsDetails->category->category,
                'tags' => $tags
            ];

            return $this->sendResponse($data, 'News fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching news by category: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching news by category');
        }
    }

    public function showByTag(Request $request)
    {
        try {
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching news by tag: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching news by tag');
        }
    }
}
