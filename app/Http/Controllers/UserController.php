<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Auth\Events\Registered;
use Spatie\Activitylog\Models\Activity;

class UserController
{
    use ApiResponse;

    public function index()
    {
        $cached = Redis::get('users.index');

        if ($cached) {
            $users = json_decode($cached);
            return $this->sendResponse($users, 'User fetched successfully from cache');
        }

        $users = User::select(['id', 'name', 'slug', 'email'])->get();

        if ($users->isEmpty()) {
            return $this->sendError('No users found', 404);
        }

        Redis::setex('users.index', 3600, json_encode($users));

        return $this->sendResponse($users, 'User fetched successfully');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8'
        ]);

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
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->sendError('User not found', 404);
        }

        $data = array_merge(['id' => $user->id], $user->only(['name', 'slug', 'email']));

        return $this->sendResponse($data, 'User fetched successfully');
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->sendError('User not found', 404);
        }

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|' . ($user->email != $request->email ? 'unique:users' : '')
        ]);

        $user->slug = null;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        $data = array_merge(['id' => $user->id], $user->only(['name', 'slug', 'email']));

        Activity::all()->last();

        Redis::del('users.index');

        return $this->sendResponse($data, 'User updated successfully');
    }

    public function destroy($id)
    {
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
    }

    public function trashed()
    {
        $cached = Redis::get('users.trashed');

        if ($cached) {
            $users = json_decode($cached);
            return $this->sendResponse($users, 'User fetched successfully from cache');
        }

        $users = User::onlyTrashed()->select(['id', 'name', 'slug', 'email'])->get();

        if ($users->isEmpty()) {
            return $this->sendError('No users found', 404);
        }

        Redis::setex('users.trashed', 3600, json_encode($users));

        return $this->sendResponse($users, 'User fetched successfully');
    }

    public function restore($id)
    {
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
    }

    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->where('id', $id)->first();

        if (!$user) {
            return $this->sendError('User not found', 404);
        }

        $user->forceDelete();

        $data = array_merge(['id' => $user->id], $user->only(['name', 'slug', 'email']));

        Activity::all()->last();

        Redis::del('users.trashed');

        return $this->sendResponse($data, 'User deleted permanently');
    }

    public function search(Request $request)
    {
        if (!$request->has('q') || empty($request->q)) {
            $cached = Redis::get('users.index');

            if ($cached) {
                $users = json_decode($cached);
                return $this->sendResponse($users, 'Users fetched successfully from cache');
            }

            $users = User::select(['id', 'name', 'slug', 'email'])->get();

            if ($users->isEmpty()) {
                return $this->sendError('No users found', 404);
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
            return $this->sendError('No users found matching your query', 404);
        }

        return $this->sendResponse($users, 'Users fetched successfully');
    }
}
