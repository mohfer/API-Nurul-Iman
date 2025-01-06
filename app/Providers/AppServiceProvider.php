<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $customUrl = str_replace(
                env('APP_URL') . '/api',
                env('FRONTEND_URL'),
                $url
            );

            return (new MailMessage)
                ->subject(Lang::get('Verify Email Address'))
                ->line(Lang::get('Please click the button below to verify your email address.'))
                ->action(Lang::get('Verify Email Address'), $customUrl)
                ->line(Lang::get('If you did not create an account, no further action is required.'));
        });

        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return env('FRONTEND_URL', 'http://localhost:8000/api') . '/auth/reset-password?token=' . $token;
        });
    }
}
