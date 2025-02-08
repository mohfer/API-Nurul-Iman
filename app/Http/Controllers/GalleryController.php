<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
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

class GalleryController
{
    use ApiResponse, GenerateRequestId;

    public function index()
    {
        try {
            $cached = Redis::get('galleries.index');

            if ($cached) {
                $galleries = json_decode($cached);
                return $this->sendResponse($galleries, 'Gallery fetched successfully from cache');
            }

            $galleries = Gallery::select(['id', 'title', 'image_url', 'description'])->orderBy('title', 'asc')->get();

            if ($galleries->isEmpty()) {
                return $this->sendResponse([], 'No galleries found');
            }

            Redis::setex('galleries.index', 3600, json_encode($galleries));

            return $this->sendResponse($galleries, 'Gallery fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fethcing galleries: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fethcing galleries');
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
                'title' => 'required|string',
                'description' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            DB::beginTransaction();

            $image = $request->file('image');
            $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();
            $filePath = env('GOOGLE_DRIVE_SUBFOLDER') . '/galleries/' . $fileName;

            Gdrive::put($filePath, $image);

            $fileMetadata = collect(Gdrive::all(env('GOOGLE_DRIVE_SUBFOLDER') . '/galleries/'))
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

            $gallery = Gallery::create([
                'title' => $request->title,
                'image_url' => $thumbnailUrl,
                'image_name' => $fileName,
                'description' => $request->description,
            ]);

            $data = array_merge(['id' => $gallery->id], $gallery->only(['title', 'image_url', 'description']));

            Activity::all()->last();

            Redis::del('galleries.index');

            DB::commit();

            return $this->sendResponse($data, 'Gallery created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during creating gallery: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while creating gallery');
        }
    }

    public function show($id)
    {
        try {
            $gallery = Gallery::find($id);

            if (!$gallery) {
                return $this->sendError('Gallery not found', 404);
            }

            $data = array_merge(['id' => $gallery->id], $gallery->only(['title', 'image_url', 'description']));

            return $this->sendResponse($data, 'Gallery fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during showing gallery: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while showing gallery');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $gallery = Gallery::find($id);

            if (!$gallery) {
                return $this->sendError('Gallery not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
                'title' => 'required|string',
                'description' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $newFileName = Str::uuid()->toString() . '.' . $image->getClientOriginalExtension();
                $newFilePath = env('GOOGLE_DRIVE_SUBFOLDER') . '/galleries/' . $newFileName;

                Gdrive::put($newFilePath, $image);

                $files = Gdrive::all(env('GOOGLE_DRIVE_SUBFOLDER') . '/galleries/');
                $filesCollection = collect($files);

                if ($gallery->image_name) {
                    $oldFile = $filesCollection->firstWhere('extra_metadata.name', $gallery->image_name);
                    if ($oldFile) {
                        try {
                            Gdrive::delete(env('GOOGLE_DRIVE_SUBFOLDER') . '/galleries/' . $gallery->image_name);
                        } catch (\Exception $e) {
                            Log::warning("Failed to delete old file: {$gallery->image_name}");
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

                $gallery->image_url = env('GOOGLE_DRIVE_URL') . $newFileMetadata['extra_metadata']['id'];
                $gallery->image_name = $newFileName;
            }

            $gallery->title = $request->title;
            $gallery->description = $request->description;
            $gallery->save();

            $data = array_merge(['id' => $gallery->id], $gallery->only(['title', 'image_url', 'description']));

            Activity::all()->last();

            Redis::del('galleries.index');

            DB::commit();

            return $this->sendResponse($data, 'Gallery updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during updating gallery: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while updating gallery');
        }
    }


    public function destroy($id)
    {
        try {
            $gallery = Gallery::find($id);

            if (!$gallery) {
                return $this->sendError('Gallery not found', 404);
            }

            $gallery->delete();

            $data = array_merge(['id' => $gallery->id], $gallery->only(['title', 'image_url', 'description']));

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('galleries.index');
                $pipe->del('galleries.trashed');
            });

            return $this->sendResponse($data, 'Gallery deleted successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during deleting gallery: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while deleting gallery');
        }
    }

    public function trashed()
    {
        try {
            $cached = Redis::get('galleries.trashed');

            if ($cached) {
                $galleries = json_decode($cached);
                return $this->sendResponse($galleries, 'Gallery fetched successfully from cache');
            }

            $galleries = Gallery::onlyTrashed()->select(['id', 'title', 'image_url', 'description'])->orderBy('title', 'asc')->get();

            if ($galleries->isEmpty()) {
                return $this->sendResponse([], 'No galleries found');
            }

            Redis::setex('galleries.trashed', 3600, json_encode($galleries));

            return $this->sendResponse($galleries, 'Gallery fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching trashed galleries: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching trashed galleries');
        }
    }

    public function restore($id)
    {
        try {
            $gallery = Gallery::onlyTrashed()->where('id', $id)->first();

            if (!$gallery) {
                return $this->sendError('Gallery not found', 404);
            }

            $gallery->restore();

            $data = array_merge(['id' => $gallery->id], $gallery->only(['title', 'image_url', 'description']));

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('galleries.index');
                $pipe->del('galleries.trashed');
            });

            return $this->sendResponse($data, 'Gallery restored successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during restoring gallery: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while restoring gallery');
        }
    }

    public function forceDelete($id)
    {
        try {
            $gallery = Gallery::onlyTrashed()->where('id', $id)->first();

            if (!$gallery) {
                return $this->sendError('Gallery not found', 404);
            }

            DB::beginTransaction();

            if ($gallery->image_name) {
                Gdrive::delete(env('GOOGLE_DRIVE_SUBFOLDER') . '/galleries/' . $gallery->image_name);
            }

            $gallery->forceDelete();

            $data = array_merge(['id' => $gallery->id], $gallery->only(['title', 'image_url', 'description']));

            Activity::all()->last();

            Redis::del('galleries.trashed');

            DB::commit();

            return $this->sendResponse($data, 'Gallery deleted permanently');
        } catch (\Exception $e) {
            DB::rollBack();
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during force deleting gallery: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while force deleting gallery');
        }
    }

    public function search(Request $request)
    {
        try {
            if (!$request->has('q') || empty($request->q)) {
                $cached = Redis::get('galleries.index');

                if ($cached) {
                    $galleries = json_decode($cached);
                    return $this->sendResponse($galleries, 'Galleries fetched successfully from cache');
                }

                $galleries = Gallery::select(['id', 'title', 'image_url', 'description'])->get();

                if ($galleries->isEmpty()) {
                    return $this->sendResponse([], 'No galleries found');
                }

                Redis::setex('galleries.index', 3600, json_encode($galleries));

                return $this->sendResponse($galleries, 'Galleries fetched successfully');
            }

            $galleries = Gallery::where('title', 'like', '%' . $request->q . '%')
                ->orWhere('description', 'like', '%' . $request->q . '%')
                ->select(['id', 'title', 'image_url', 'description'])
                ->get();

            if ($galleries->isEmpty()) {
                return $this->sendResponse([], 'No galleries found matching your query');
            }

            return $this->sendResponse($galleries, 'Galleries fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during searching galleries: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while searching galleries');
        }
    }
}
