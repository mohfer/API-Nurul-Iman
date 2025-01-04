<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController
{
    use ApiResponse;

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
            'remember_me' => 'required|boolean'
        ]);

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return $this->sendError('Invalid credentials', 401);
        }

        $user = Auth::user();

        $userDetails = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ];

        if ($request->remember_me) {
            $token = $user->createToken('auth_token', ['*'], now()->addWeeks(1))->plainTextToken;
        } else {
            $token = $user->createToken('auth_token', ['*'], now()->addHours(1))->plainTextToken;
        }

        $data = [
            'token' => $token,
            'user' => $userDetails,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')
        ];

        activity('auth')
            ->event('login')
            ->log('user logged in');

        return $this->sendResponse($data, 'User logged in successfully');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        activity('auth')
            ->event('logout')
            ->log('user logged out');

        return $this->sendResponse(null, 'User logged out successfully');
    }
}
