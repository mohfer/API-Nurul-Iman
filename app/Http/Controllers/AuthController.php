<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
            'remember_me' => 'boolean'
        ]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();

            $userDetails = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ];

            activity()
                ->event('login')
                ->log('User logged in');

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

            return $this->sendResponse($data, 'Login successful');
        }

        return $this->sendError('Invalid credentials', 401);
    }

    public function logout(Request $request)
    {
        activity()
            ->event('logout')
            ->log('User logged out');

        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse(null, 'User logged out successfully');
    }
}
