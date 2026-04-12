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
        return "/api/v1/merchant/users/{$id}";
    }

    protected static function deleteMerchantUserEndpoint(string $id): string
    {
        return "/api/v1/merchant/users/{$id}";
    }

    protected static function listMerchantCreditPackagesEndpoint(string $merchantId): string
    {
        return "/api/v1/merchants/{$merchantId}/packages";
    }

    protected static function listMerchantTransactionsEndpoint(): string
    {
        return '/api/v1/merchant/transactions';
    }

    protected static function listCreditPackagesEndpoint(): string
    {
        return '/api/v1/merchant/packages';
    }

    protected static function createCreditPackageEndpoint(): string
    {
        return '/api/v1/merchant/packages';
    }

    protected static function updateCreditPackageEndpoint(string $id): string
    {
        return "/api/v1/merchant/packages/{$id}";
    }

    protected static function deleteCreditPackageEndpoint(string $id): string
    {
        return "/api/v1/merchant/packages/{$id}";
    }
}
