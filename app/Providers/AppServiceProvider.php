<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Password::defaults(function () {
            return Password::min(12)
                ->max(255)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinutes(
            config('rate_limiting.login.decay_minutes'),
            config('rate_limiting.login.attempts'),
        )->by($request->ip()));

        RateLimiter::for('forgot-password', fn (Request $request) => Limit::perMinutes(
            config('rate_limiting.forgot_password.decay_minutes'),
            config('rate_limiting.forgot_password.attempts'),
        )->by($request->ip().'|'.$request->input('email')));

        RateLimiter::for('resend-verification', fn (Request $request) => Limit::perMinutes(
            config('rate_limiting.resend_verification.decay_minutes'),
            config('rate_limiting.resend_verification.attempts'),
        )->by($request->ip().'|'.$request->user()?->id));
    }
}
