<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Traits\GenerateRequestId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Validator;

class RoleController
{
    use ApiResponse, GenerateRequestId;

    public function index()
    {
        try {
            $cached = Redis::get('roles.index');

            if ($cached) {
                $roles = json_decode($cached);
                return $this->sendResponse($roles, 'Role fetched successfully from cache');
            }

            $roles = Role::select(['uuid', 'name'])->orderBy('name', 'asc')->get();

            $rolesData = $roles->map(function ($role) {
                return array_merge(['uuid' => $role->uuid], $role->only($role->getFillable()), [
                    'permissions' => $role->getAllPermissions()->pluck('name')->toArray()
                ]);
            });

            if ($roles->isEmpty()) {
                return $this->sendResponse([], 'No roles found');
            }

            Redis::setex('roles.index', 3600, json_encode($rolesData));

            return $this->sendResponse($rolesData, 'Role fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fethcing roles: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fethcing roles');
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|unique:roles',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,name'
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
            ]);

            if ($request->has('permissions')) {
                $role->givePermissionTo($request->permissions);
            }

            $data = array_merge(['uuid' => $role->uuid], $role->only($role->getFillable()), [
                'permissions' => $role->getAllPermissions()->pluck('name')->toArray()
            ]);

            Activity::all()->last();

            Redis::del('roles.index');

            DB::commit();

            return $this->sendResponse($data, 'Role created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during creating role: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while creating role');
        }
    }

    public function show($id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return $this->sendError('Role not found', 404);
            }

            $data = array_merge(['uuid' => $role->uuid], $role->only($role->getFillable()), [
                'permissions' => $role->getAllPermissions()->pluck('name')->toArray()
            ]);

            return $this->sendResponse($data, 'Role fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during fetching role: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while fetching role');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return $this->sendError('Role not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|' . ($role->name != $request->name ? 'unique:categories' : ''),
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,name'
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            DB::beginTransaction();

            $role->name = $request->name;
            $role->save();

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            $data = array_merge(['uuid' => $role->uuid], $role->only($role->getFillable()), [
                'permissions' => $role->getAllPermissions()->pluck('name')->toArray()
            ]);

            Activity::all()->last();

            Redis::del('roles.index');

            DB::commit();

            return $this->sendResponse($data, 'Role updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during updating role: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while updating role');
        }
    }

    public function destroy($id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return $this->sendError('Role not found', 404);
            }

            $data = array_merge(['uuid' => $role->uuid], $role->only($role->getFillable()), [
                'permissions' => $role->getAllPermissions()->pluck('name')->toArray()
            ]);

            DB::beginTransaction();

            if ($role->permissions->isNotEmpty()) {
                $role->syncPermissions([]);
            }

            $role->delete();

            Activity::all()->last();

            Redis::del('roles.index');

            DB::commit();

            return $this->sendResponse($data, 'Role deleted successfully and permissions revoked');
        } catch (\Exception $e) {
            DB::rollBack();
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during deleting role: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while deleting role');
        }
    }

    public function search(Request $request)
    {
        try {
            if (!$request->has('q') || empty($request->q)) {
                $cached = Redis::get('roles.index');

                if ($cached) {
                    $roles = json_decode($cached);
                    return $this->sendResponse($roles, 'Roles fetched successfully from cache');
                }

                $roles = Role::with('permissions:name')->select(['uuid', 'name'])->get();

                $rolesData = $roles->map(function ($role) {
                    return array_merge(['uuid' => $role->uuid], $role->only($role->getFillable()), [
                        'permissions' => $role->getAllPermissions()->pluck('name')->toArray()
                    ]);
                });

                if ($roles->isEmpty()) {
                    return $this->sendResponse([], 'No roles found');
                }

                Redis::setex('roles.index', 3600, json_encode($rolesData));

                return $this->sendResponse($rolesData, 'Roles fetched successfully');
            }

            $roles = Role::where('name', 'like', '%' . $request->q . '%')
                ->select(['uuid', 'name'])
                ->get();

            $rolesData = $roles->map(function ($role) {
                return array_merge(['uuid' => $role->uuid], $role->only($role->getFillable()), [
                    'permissions' => $role->getAllPermissions()->pluck('name')->toArray()
                ]);
            });

            if ($roles->isEmpty()) {
                return $this->sendResponse([], 'No roles found matching your query');
            }

            return $this->sendResponse($rolesData, 'Roles fetched successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during searching roles: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while searching roles');
        }
    }
}
