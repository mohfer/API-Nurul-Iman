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

    public function show(User $user)
    {
        //
    }

    public function update(Request $request, User $user)
    {
        //
    }

    public function destroy(User $user)
    {
        //
    }
}
