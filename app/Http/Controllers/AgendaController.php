<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Traits\GenerateRequestId;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Validator;

class AgendaController
{
    use ApiResponse, GenerateRequestId;

    public function index()
    {
        try {
            $cached = Redis::get('agendas.index');

            if ($cached) {
                $agendas = json_decode($cached);
                return $this->sendResponse($agendas, 'Agenda fetched successfully from cache');
            }

            $agendas = Agenda::select(['id', 'title', 'slug', 'description', 'date', 'created_at'])->get();

            if ($agendas->isEmpty()) {
                return $this->sendResponse([], 'No agendas found');
            }

            Redis::setex('agendas.index', 3600, json_encode($agendas));

            return $this->sendResponse($agendas, 'Agenda fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching agendas: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching agendas');
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'description' => 'required|string',
                'date' => 'required|date'
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            $agenda = Agenda::create([
                'title' => $request->title,
                'description' => $request->description,
                'date' => $request->date
            ]);

            $data = array_merge(['id' => $agenda->id], $agenda->only($agenda->getFillable()), ['created_at' => $agenda->created_at]);

            Activity::all()->last();

            Redis::del('agendas.index');

            return $this->sendResponse($data, 'Agenda created successfully', 201);
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during creating agenda: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while creating agenda');
        }
    }

    public function show($id)
    {
        try {
            $agenda = Agenda::find($id);

            if (!$agenda) {
                return $this->sendError('Agenda not found', 404);
            }

            $data = array_merge(['id' => $agenda->id], $agenda->only($agenda->getFillable()), ['created_at' => $agenda->created_at]);

            return $this->sendResponse($data, 'Agenda fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during showing agenda: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while showing agenda');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $agenda = Agenda::find($id);

            if (!$agenda) {
                return $this->sendError('Agenda not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'description' => 'required|string',
                'date' => 'required|date'
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            $agenda->slug = null;
            $agenda->title = $request->title;
            $agenda->description = $request->description;
            $agenda->date = $request->date;
            $agenda->save();

            $data = array_merge(['id' => $agenda->id], $agenda->only($agenda->getFillable()), ['created_at' => $agenda->created_at]);

            Activity::all()->last();

            Redis::del('agendas.index');

            return $this->sendResponse($data, 'Agenda updated successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during updating agenda: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while updating agenda');
        }
    }

    public function destroy($id)
    {
        try {
            $agenda = Agenda::find($id);

            if (!$agenda) {
                return $this->sendError('Agenda not found', 404);
            }

            $agenda->delete();

            $data = array_merge(['id' => $agenda->id], $agenda->only($agenda->getFillable()), ['created_at' => $agenda->created_at]);

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('agendas.index');
                $pipe->del('agendas.trashed');
            });

            return $this->sendResponse($data, 'Agenda deleted successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during deleting agenda: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while deleting agenda');
        }
    }

    public function trashed()
    {
        try {
            $cached = Redis::get('agendas.trashed');

            if ($cached) {
                $agendas = json_decode($cached);
                return $this->sendResponse($agendas, 'Agenda fetched successfully from cache');
            }

            $agendas = Agenda::onlyTrashed()->select(['id', 'title', 'slug', 'description', 'date', 'created_at'])->get();

            if ($agendas->isEmpty()) {
                return $this->sendResponse([], 'No agendas found');
            }

            Redis::setex('agendas.trashed', 3600, json_encode($agendas));

            return $this->sendResponse($agendas, 'Agenda fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fething trashed agendas: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching trashed agendas');
        }
    }

    public function restore($id)
    {
        try {
            $agenda = Agenda::onlyTrashed()->where('id', $id)->first();

            if (!$agenda) {
                return $this->sendError('Agenda not found', 404);
            }

            $agenda->restore();

            $data = array_merge(['id' => $agenda->id], $agenda->only($agenda->getFillable()), ['created_at' => $agenda->created_at]);

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('agendas.index');
                $pipe->del('agendas.trashed');
            });

            return $this->sendResponse($data, 'Agenda restored successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during restoring agenda: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while restoring agenda');
        }
    }

    public function forceDelete($id)
    {
        try {
            $agenda = Agenda::onlyTrashed()->where('id', $id)->first();

            if (!$agenda) {
                return $this->sendError('Agenda not found', 404);
            }

            $agenda->forceDelete();

            $data = array_merge(['id' => $agenda->id], $agenda->only($agenda->getFillable()), ['created_at' => $agenda->created_at]);

            Activity::all()->last();

            Redis::del('agendas.trashed');

            return $this->sendResponse($data, 'Agenda deleted permanently');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during force deleting agenda: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while force deleting agenda');
        }
    }

    public function search(Request $request)
    {
        try {
            if (!$request->has('q') || empty($request->q)) {
                $cached = Redis::get('agendas.index');

                if ($cached) {
                    $agendas = json_decode($cached);
                    return $this->sendResponse($agendas, 'Agendas fetched successfully from cache');
                }

                $agendas = Agenda::select(['id', 'title', 'slug', 'description', 'date', 'created_at'])->get();

                if ($agendas->isEmpty()) {
                    return $this->sendResponse([], 'No agendas found');
                }

                Redis::setex('agendas.index', 3600, json_encode($agendas));

                return $this->sendResponse($agendas, 'Agendas fetched successfully');
            }

            $agendas = Agenda::where('title', 'like', '%' . $request->q . '%')
                ->orWhere('date', 'like', '%' . $request->q . '%')
                ->select(['id', 'title', 'slug', 'description', 'date', 'created_at'])
                ->get();

            if ($agendas->isEmpty()) {
                return $this->sendResponse([], 'No agendas found matching your query');
            }

            return $this->sendResponse($agendas, 'Agendas fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during searching agendas: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while searching agendas');
        }
    }
}
