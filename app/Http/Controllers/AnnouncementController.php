<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Traits\GenerateRequestId;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Validator;

class AnnouncementController
{
    use ApiResponse, GenerateRequestId;

    public function index()
    {
        try {
            $cached = Redis::get('announcements.index');

            if ($cached) {
                $announcements = json_decode($cached);
                return $this->sendResponse($announcements, 'Announcement fetched successfully from cache');
            }

            $announcements = Announcement::select(['id', 'title', 'slug', 'description', 'created_at'])->get();

            if ($announcements->isEmpty()) {
                return $this->sendResponse([], 'No announcements found');
            }

            Redis::setex('announcements.index', 3600, json_encode($announcements));

            return $this->sendResponse($announcements, 'Announcement fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching announcements: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching announcements');
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'description' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            $announcement = Announcement::create([
                'title' => $request->title,
                'description' => $request->description
            ]);

            $data = array_merge(['id' => $announcement->id], $announcement->only($announcement->getFillable()), ['created_at' => $announcement->created_at]);

            Activity::all()->last();

            Redis::del('announcements.index');

            return $this->sendResponse($data, 'Announcement created successfully', 201);
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during creating announcement: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while creating announcement');
        }
    }

    public function show($id)
    {
        try {
            $announcement = Announcement::find($id);

            if (!$announcement) {
                return $this->sendError('Announcement not found', 404);
            }

            $data = array_merge(['id' => $announcement->id], $announcement->only($announcement->getFillable()), ['created_at' => $announcement->created_at]);

            return $this->sendResponse($data, 'Announcement fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during showing announcement: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while showing announcement');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $announcement = Announcement::find($id);

            if (!$announcement) {
                return $this->sendError('Announcement not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'description' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            $announcement->slug = null;
            $announcement->title = $request->title;
            $announcement->description = $request->description;
            $announcement->save();

            $data = array_merge(['id' => $announcement->id], $announcement->only($announcement->getFillable()), ['created_at' => $announcement->created_at]);

            Activity::all()->last();

            Redis::del('announcements.index');

            return $this->sendResponse($data, 'Announcement updated successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during updating announcement: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while updating announcement');
        }
    }

    public function destroy($id)
    {
        try {
            $announcement = Announcement::find($id);

            if (!$announcement) {
                return $this->sendError('Announcement not found', 404);
            }

            $announcement->delete();

            $data = array_merge(['id' => $announcement->id], $announcement->only($announcement->getFillable()), ['created_at' => $announcement->created_at]);

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('announcements.index');
                $pipe->del('announcements.trashed');
            });

            return $this->sendResponse($data, 'Announcement deleted successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during deleting announcement: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while deleting announcement');
        }
    }

    public function trashed()
    {
        try {
            $cached = Redis::get('announcements.trashed');

            if ($cached) {
                $announcements = json_decode($cached);
                return $this->sendResponse($announcements, 'Announcement fetched successfully from cache');
            }

            $announcements = Announcement::onlyTrashed()->select(['id', 'title', 'slug', 'description', 'created_at'])->get();

            if ($announcements->isEmpty()) {
                return $this->sendResponse([], 'No announcements found');
            }

            Redis::setex('announcements.trashed', 3600, json_encode($announcements));

            return $this->sendResponse($announcements, 'Announcement fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fething trashed announcements: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching trashed announcements');
        }
    }

    public function restore($id)
    {
        try {
            $announcement = Announcement::onlyTrashed()->where('id', $id)->first();

            if (!$announcement) {
                return $this->sendError('Announcement not found', 404);
            }

            $announcement->restore();

            $data = array_merge(['id' => $announcement->id], $announcement->only($announcement->getFillable()), ['created_at' => $announcement->created_at]);

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('announcements.index');
                $pipe->del('announcements.trashed');
            });

            return $this->sendResponse($data, 'Announcement restored successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during restoring announcement: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while restoring announcement');
        }
    }

    public function forceDelete($id)
    {
        try {
            $announcement = Announcement::onlyTrashed()->where('id', $id)->first();

            if (!$announcement) {
                return $this->sendError('Announcement not found', 404);
            }

            $announcement->forceDelete();

            $data = array_merge(['id' => $announcement->id], $announcement->only($announcement->getFillable()), ['created_at' => $announcement->created_at]);

            Activity::all()->last();

            Redis::del('announcements.trashed');

            return $this->sendResponse($data, 'Announcement deleted permanently');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during force deleting announcement: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while force deleting announcement');
        }
    }

    public function search(Request $request)
    {
        try {
            if (!$request->has('q') || empty($request->q)) {
                $cached = Redis::get('announcements.index');

                if ($cached) {
                    $announcements = json_decode($cached);
                    return $this->sendResponse($announcements, 'Announcements fetched successfully from cache');
                }

                $announcements = Announcement::select(['id', 'title', 'slug', 'description', 'created_at'])->get();

                if ($announcements->isEmpty()) {
                    return $this->sendResponse([], 'No announcements found');
                }

                Redis::setex('announcements.index', 3600, json_encode($announcements));

                return $this->sendResponse($announcements, 'Announcements fetched successfully');
            }

            $announcements = Announcement::where('title', 'like', '%' . $request->q . '%')
                ->select(['id', 'title', 'slug', 'description', 'created_at'])
                ->get();

            if ($announcements->isEmpty()) {
                return $this->sendResponse([], 'No announcements found matching your query');
            }

            return $this->sendResponse($announcements, 'Announcements fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during searching announcements: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while searching announcements');
        }
    }
}
