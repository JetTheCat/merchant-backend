<?php

declare(strict_types=1);

namespace Tests\Feature\Traits;

trait BroadcastingEndpoints
{
    protected const string MERCHANT_USER_GUARD = 'merchant_user';

    protected const string USER_GUARD = 'sanctum';

    protected static function broadcastAuthEndpoint(): string
    {
        return '/broadcasting/auth';
    }
}
