<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Storage\FeeReportStorageInterface;
use App\Services\Storage\FeeReportStorageService;
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
        $this->app->singleton(FeeReportStorageInterface::class, FeeReportStorageService::class);
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

        RateLimiter::for('redemption-qr', fn (Request $request) => Limit::perMinutes(
            config('rate_limiting.redemption_qr.decay_minutes'),
            config('rate_limiting.redemption_qr.attempts'),
        )->by($request->user()?->id));
    }
}
