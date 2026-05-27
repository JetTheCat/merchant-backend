<?php

declare(strict_types=1);

namespace Tests\Api\v1\Traits;

trait MerchantPortalEndpoints
{
    protected const MERCHANT_USER_GUARD = 'merchant_user';

    protected static function getAuthenticatedMerchantUserEndpoint(): string
    {
        return '/api/v1/merchant/users/me';
    }

    protected static function patchMerchantUserPasswordEndpoint(): string
    {
        return '/api/v1/merchant/users/me/password';
    }

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

    protected static function createMerchantStaffEndpoint(): string
    {
        return '/api/v1/merchant/staff';
    }

    protected static function patchMerchantStaffEndpoint(string $id): string
    {
        return "/api/v1/merchant/staff/{$id}";
    }

    protected static function deleteMerchantStaffEndpoint(string $id): string
    {
        return "/api/v1/merchant/staff/{$id}";
    }

    protected static function listMerchantCreditPackagesEndpoint(string $merchantId): string
    {
        return "/api/v1/merchants/{$merchantId}/packages";
    }

    protected static function getMerchantStatsEndpoint(): string
    {
        return '/api/v1/merchant/stats';
    }

    protected static function listMerchantTransactionsEndpoint(): string
    {
        return '/api/v1/merchant/transactions';
    }

    protected static function generateFallbackCodeEndpoint(): string
    {
        return '/api/v1/merchant/redemptions/code';
    }

    protected static function listMerchantRedemptionsEndpoint(): string
    {
        return '/api/v1/merchant/redemptions';
    }

    protected static function refundRedemptionEndpoint(string $id): string
    {
        return "/api/v1/merchant/redemptions/{$id}/refund";
    }

    protected static function getTodayRedemptionSummaryEndpoint(): string
    {
        return '/api/v1/merchant/redemptions/today';
    }

    protected static function listMerchantLocationsEndpoint(): string
    {
        return '/api/v1/merchant/locations';
    }

    protected static function listSettlementsEndpoint(): string
    {
        return '/api/v1/merchant/settlements';
    }

    protected static function getSettlementEndpoint(string $id): string
    {
        return "/api/v1/merchant/settlements/{$id}";
    }

    protected static function getSettlementInvoiceEndpoint(string $id): string
    {
        return "/api/v1/merchant/settlements/{$id}/invoice";
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
