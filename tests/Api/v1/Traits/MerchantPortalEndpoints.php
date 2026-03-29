<?php

declare(strict_types=1);

namespace Tests\Api\v1\Traits;

trait MerchantPortalEndpoints
{
    protected const MERCHANT_USER_GUARD = 'merchant_user';

    protected static function getMerchantProfileEndpoint(): string
    {
        return '/api/v1/merchant';
    }

    protected static function patchMerchantProfileEndpoint(): string
    {
        return '/api/v1/merchant';
    }

    protected static function getMerchantUsersEndpoint(): string
    {
        return '/api/v1/merchant/users';
    }

    protected static function patchMerchantUserEndpoint(string $id): string
    {
        return '/api/v1/merchant/users/'.$id;
    }

    protected static function deleteMerchantUserEndpoint(string $id): string
    {
        return '/api/v1/merchant/users/'.$id;
    }
}
