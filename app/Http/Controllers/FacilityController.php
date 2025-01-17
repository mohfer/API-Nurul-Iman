<?php

namespace App\Http\Controllers;

use App\Models\Facility;
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

class FacilityController
{
    use ApiResponse, GenerateRequestId;

    public function index()
    {
        try {
            $cached = Redis::get('facilities.index');

            if ($cached) {
                $facilities = json_decode($cached);
                return $this->sendResponse($facilities, 'facility fetched successfully from cache');
            }

            $facilities = Facility::select(['id', 'title', 'image_url', 'description'])->get();

            if ($facilities->isEmpty()) {
                return $this->sendResponse([], 'No facilities found');
            }

            Redis::setex('facilities.index', 3600, json_encode($facilities));

            return $this->sendResponse($facilities, 'facility fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fethcing facilities: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fethcing facilities');
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
            $filePath = env('GOOGLE_DRIVE_SUBFOLDER') . '/facilities/' . $fileName;

            Gdrive::put($filePath, $image);

            $fileMetadata = collect(Gdrive::all(env('GOOGLE_DRIVE_SUBFOLDER') . '/facilities/'))
                ->firstWhere('extra_metadata.name', $fileName);

            if (!$fileMetadata) {
                throw new \Exception("The metadata file was not found in Google Drive.");
            }

            $thumbnailUrl = env('GOOGLE_DRIVE_URL') . $fileMetadata['extra_metadata']['id'];

            $facility = Facility::create([
                'title' => $request->title,
                'image_url' => $thumbnailUrl,
                'image_name' => $fileName,
                'description' => $request->description,
            ]);

            $data = array_merge(['id' => $facility->id], $facility->only(['title', 'image_url', 'description']));

            Activity::all()->last();

            Redis::del('facilities.index');

            DB::commit();

            return $this->sendResponse($data, 'Facility created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during creating facility: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while creating facility');
        }
    }

    public function show($id)
    {
        try {
            $facility = Facility::find($id);

            if (!$facility) {
                return $this->sendError('Facility not found', 404);
            }

            $data = array_merge(['id' => $facility->id], $facility->only(['title', 'image_url', 'description']));

            return $this->sendResponse($data, 'Facility fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during showing facility: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while showing facility');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $facility = Facility::find($id);

            if (!$facility) {
                return $this->sendError('Facility not found', 404);
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
                $newFilePath = env('GOOGLE_DRIVE_SUBFOLDER') . '/facilities/' . $newFileName;

                Gdrive::put($newFilePath, $image);

                $files = Gdrive::all(env('GOOGLE_DRIVE_SUBFOLDER') . '/facilities/');
                $filesCollection = collect($files);

                if ($facility->image_name) {
                    $oldFile = $filesCollection->firstWhere('extra_metadata.name', $facility->image_name);
                    if ($oldFile) {
                        try {
                            Gdrive::delete(env('GOOGLE_DRIVE_SUBFOLDER') . '/facilities/' . $facility->image_name);
                        } catch (\Exception $e) {
                            Log::warning("Failed to delete old file: {$facility->image_name}");
                        }
                    }
                }

                $newFileMetadata = $filesCollection->firstWhere('extra_metadata.name', $newFileName);
                if (!$newFileMetadata) {
                    throw new \Exception("Failed to get the metadata of the newly uploaded file.");
                }

                $facility->image_url = env('GOOGLE_DRIVE_URL') . $newFileMetadata['extra_metadata']['id'];
                $facility->image_name = $newFileName;
            }

            $facility->title = $request->title;
            $facility->description = $request->description;
            $facility->save();

            $data = array_merge(['id' => $facility->id], $facility->only(['title', 'image_url', 'description']));

            Activity::all()->last();

            Redis::del('facilities.index');

            DB::commit();

            return $this->sendResponse($data, 'Facility updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during updating facility: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while updating facility');
        }
    }

    public function destroy($id)
    {
        try {
            $facility = Facility::find($id);

            if (!$facility) {
                return $this->sendError('Facility not found', 404);
            }

            $facility->delete();

            $data = array_merge(['id' => $facility->id], $facility->only(['title', 'image_url', 'description']));

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('facilities.index');
                $pipe->del('facilities.trashed');
            });

            return $this->sendResponse($data, 'Facility deleted successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during deleting facility: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while deleting facility');
        }
    }

    public function trashed()
    {
        try {
            $cached = Redis::get('facilities.trashed');

            if ($cached) {
                $facilities = json_decode($cached);
                return $this->sendResponse($facilities, 'facility fetched successfully from cache');
            }

            $facilities = Facility::onlyTrashed()->select(['id', 'title', 'image_url', 'description'])->get();

            if ($facilities->isEmpty()) {
                return $this->sendResponse([], 'No facilities found');
            }

            Redis::setex('facilities.trashed', 3600, json_encode($facilities));

            return $this->sendResponse($facilities, 'facility fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching trashed facilities: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching trashed facilities');
        }
    }

    public function restore($id)
    {
        try {
            $facility = Facility::onlyTrashed()->where('id', $id)->first();

            if (!$facility) {
                return $this->sendError('Facility not found', 404);
            }

            $facility->restore();

            $data = array_merge(['id' => $facility->id], $facility->only(['title', 'image_url', 'description']));

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('facilities.index');
                $pipe->del('facilities.trashed');
            });

            return $this->sendResponse($data, 'Facility restored successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during restoring facility: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while restoring facility');
        }
    }

    public function forceDelete($id)
    {
        try {
            $facility = Facility::onlyTrashed()->where('id', $id)->first();

            if (!$facility) {
                return $this->sendError('Facility not found', 404);
            }

            DB::beginTransaction();

            if ($facility->image_name) {
                Gdrive::delete(env('GOOGLE_DRIVE_SUBFOLDER') . '/facilities/' . $facility->image_name);
            }

            $facility->forceDelete();

            $data = array_merge(['id' => $facility->id], $facility->only(['title', 'image_url', 'description']));

            Activity::all()->last();

            Redis::del('facilities.trashed');

            DB::commit();

            return $this->sendResponse($data, 'Facility deleted permanently');
        } catch (\Exception $e) {
            DB::rollBack();
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during force deleting facility: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while force deleting facility');
        }
    }

    public function search(Request $request)
    {
        try {
            if (!$request->has('q') || empty($request->q)) {
                $cached = Redis::get('facilities.index');

                if ($cached) {
                    $facilities = json_decode($cached);
                    return $this->sendResponse($facilities, 'Facilities fetched successfully from cache');
                }

                $facilities = Facility::select(['id', 'title', 'image_url', 'description'])->get();

                if ($facilities->isEmpty()) {
                    return $this->sendResponse([], 'No facilities found');
                }

                Redis::setex('facilities.index', 3600, json_encode($facilities));

                return $this->sendResponse($facilities, 'Facilities fetched successfully');
            }

            $facilities = Facility::where('title', 'like', '%' . $request->q . '%')
                ->orWhere('description', 'like', '%' . $request->q . '%')
                ->select(['id', 'title', 'image_url', 'description'])
                ->get();

            if ($facilities->isEmpty()) {
                return $this->sendResponse([], 'No facilities found matching your query');
            }

            return $this->sendResponse($facilities, 'Facilities fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during searching facilities: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while searching facilities');
        }
    }
}
