<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\StripeService;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class StripeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(StripeService::class, fn () => new StripeService(
            new StripeClient(config('stripe.secret')),
        ));
    }
}
