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

    protected static function updatePasswordEndpoint(): string
    {
        return '/api/v1/user/password';
    }

    protected static function listUserCreditsEndpoint(): string
    {
        return '/api/v1/user/credits';
    }

    protected static function getUserCreditEndpoint(string $merchantId): string
    {
        return "/api/v1/user/credits/{$merchantId}";
    }

    protected static function listCreditTransactionsEndpoint(): string
    {
        return '/api/v1/user/credits/transactions';
    }

    protected static function getCreditTransactionEndpoint(string $id): string
    {
        return "/api/v1/user/credits/transactions/{$id}";
    }

    protected static function confirmRedemptionEndpoint(): string
    {
        return '/api/v1/user/credits/redeem';
    }
}
