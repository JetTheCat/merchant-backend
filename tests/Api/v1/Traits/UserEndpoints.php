<?php

declare(strict_types=1);

namespace Tests\Api\v1\Traits;

trait UserEndpoints
{
    protected static function getUserEndpoint(): string
    {
        return '/api/v1/user';
    }

    protected static function updateUserEndpoint(): string
    {
        return '/api/v1/user';
    }
}
