<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\GenerateRequestId;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Validator;

class AuthController
{
    use ApiResponse, GenerateRequestId;

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|min:8',
                'remember_me' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return $this->sendError('Invalid credentials', 422);
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
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during login: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while login');
        }
    }

    public function verificationEmail(EmailVerificationRequest $request)
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                return $this->sendError('Email already verified', 422);
            }

            $request->fulfill();

            activity('auth')
                ->event('verify_email')
                ->log('user verified email');

            return $this->sendResponse(null, 'Email verified successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during verification email: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while verification email');
        }
    }

    public function resendVerificationEmail(Request $request)
    {
        try {
            $request->user()->sendEmailVerificationNotification();

            activity('auth')
                ->event('resend_verify_email')
                ->log('user resent verification email');

            return $this->sendResponse(null, 'Verification email sent successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during resending verification email: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while resending verification email');
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users',
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            $status = Password::sendResetLink(
                $request->only('email')
            );

            activity('auth')
                ->event('forgot_password')
                ->log('user forgot password');

            return $status === Password::RESET_LINK_SENT
                ? $this->sendResponse(null, 'Reset password link sent successfully')
                : $this->sendError('Error occurred', 422);
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during forgoting password: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while forgoting password');
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            activity('auth')
                ->event('reset_password')
                ->log('user reset password');

            return $status === Password::PASSWORD_RESET
                ? $this->sendResponse(null, 'Password reset successfully')
                : $this->sendError('Error Occurred', 422);
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during reseting password: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while reseting password');
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => 'required|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->sendErrorWithValidation($validator->errors());
            }

            if (!Hash::check($request->current_password, $request->user()->password)) {
                return $this->sendError('Current password does not match', 422);
            }

            $request->user()->update([
                'password' => Hash::make($request->new_password)
            ]);

            activity('auth')
                ->event('change_password')
                ->log('user changed password');

            return $this->sendResponse(null, 'Password changed successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during changing password: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while changing password');
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            activity('auth')
                ->event('logout')
                ->log('user logged out');

            return $this->sendResponse(null, 'User logged out successfully');
        } catch (\Exception $e) {
            $requestId = $this->generateRequestId();
            Log::error($requestId . ' ' . ' Error during logout: ' . $e->getMessage());
            return $this->sendErrorWithRequestId($requestId, 'An error occurred while logout');
        }
    }
}
