<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Traits\GenerateRequestId;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Auth\Events\Registered;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Validator;

class UserController
{
    use ApiResponse, GenerateRequestId;

    public function index()
    {
        try {
            $cached = Redis::get('users.index');

            if ($cached) {
                $users = json_decode($cached);
                return $this->sendResponse($users, 'User fetched successfully from cache');
            }

            $users = User::select(['id', 'name', 'slug', 'email'])->get();

            if ($users->isEmpty()) {
                return $this->sendResponse([], 'No users found');
            }

            Redis::setex('users.index', 3600, json_encode($users));

            return $this->sendResponse($users, 'User fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching users: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching users');
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8'
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password)
            ]);

            event(new Registered($user));

            $data = array_merge(['id' => $user->id], $user->only(['name', 'slug', 'email']));

            Activity::all()->last();

            Redis::del('users.index');

            return $this->sendResponse($data, 'User created successfully', 201);
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during creating user: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while creating user');
        }
    }

    public function show($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->sendError('User not found', 404);
            }

            $data = array_merge(['id' => $user->id], $user->only(['name', 'slug', 'email']));

            return $this->sendResponse($data, 'User fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during showing user: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while showing user');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->sendError('User not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|email|' . ($user->email != $request->email ? 'unique:users' : '')
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            $user->slug = null;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();

            $data = array_merge(['id' => $user->id], $user->only(['name', 'slug', 'email']));

            Activity::all()->last();

            Redis::del('users.index');

            return $this->sendResponse($data, 'User updated successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during updating user: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while updating user');
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->sendError('User not found', 404);
            }

            $user->delete();

            $data = array_merge(['id' => $user->id], $user->only(['name', 'slug', 'email']));

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('users.index');
                $pipe->del('users.trashed');
            });

            return $this->sendResponse($data, 'User deleted successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during deleting user: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while deleting user');
        }
    }

    public function trashed()
    {
        try {
            $cached = Redis::get('users.trashed');

            if ($cached) {
                $users = json_decode($cached);
                return $this->sendResponse($users, 'User fetched successfully from cache');
            }

            $users = User::onlyTrashed()->select(['id', 'name', 'slug', 'email'])->get();

            if ($users->isEmpty()) {
                return $this->sendResponse([], 'No users found');
            }

            Redis::setex('users.trashed', 3600, json_encode($users));

            return $this->sendResponse($users, 'User fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching trashed users: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching trashed users');
        }
    }

    public function restore($id)
    {
        try {
            $user = User::onlyTrashed()->where('id', $id)->first();

            if (!$user) {
                return $this->sendError('User not found', 404);
            }

            $user->restore();

            $data = array_merge(['id' => $user->id], $user->only(['name', 'slug', 'email']));

            Activity::all()->last();

            Redis::pipeline(function ($pipe) {
                $pipe->del('users.index');
                $pipe->del('users.trashed');
            });

            return $this->sendResponse($data, 'User restored successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during restoring user: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while restoring user');
        }
    }

    public function forceDelete($id)
    {
        try {
            $user = User::onlyTrashed()->where('id', $id)->first();

            if (!$user) {
                return $this->sendError('User not found', 404);
            }

            $user->forceDelete();

            $data = array_merge(['id' => $user->id], $user->only(['name', 'slug', 'email']));

            Activity::all()->last();

            Redis::del('users.trashed');

            return $this->sendResponse($data, 'User deleted permanently');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during force deleting user: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while force deleting user');
        }
    }

    public function search(Request $request)
    {
        try {
            if (!$request->has('q') || empty($request->q)) {
                $cached = Redis::get('users.index');

                if ($cached) {
                    $users = json_decode($cached);
                    return $this->sendResponse($users, 'Users fetched successfully from cache');
                }

                $users = User::select(['id', 'name', 'slug', 'email'])->get();

                if ($users->isEmpty()) {
                    return $this->sendResponse([], 'No users found');
                }

                Redis::setex('users.index', 3600, json_encode($users));

                return $this->sendResponse($users, 'Users fetched successfully');
            }

            $users = User::where('name', 'like', '%' . $request->q . '%')
                ->orWhere('slug', 'like', '%' . $request->q . '%')
                ->orWhere('email', 'like', '%' . $request->q . '%')
                ->select(['id', 'name', 'slug', 'email'])
                ->get();

            if ($users->isEmpty()) {
                return $this->sendResponse([], 'No users found matching your query');
            }

            return $this->sendResponse($users, 'Users fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during searching users: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while searching users');
        }
    }
}
