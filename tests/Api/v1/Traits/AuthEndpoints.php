<?php

declare(strict_types=1);

namespace Tests\Api\v1\Traits;

trait AuthEndpoints
{
    protected static function registerEndpoint(): string
    {
        return '/api/v1/auth/register';
    }

    protected static function loginEndpoint(): string
    {
        return '/api/v1/auth/login';
    }

    protected static function logoutEndpoint(): string
    {
        return '/api/v1/auth/logout';
    }

    protected static function userEndpoint(): string
    {
        return '/api/v1/user';
    }

    protected static function resendVerificationEmailEndpoint(): string
    {
        return '/api/v1/auth/email-verification/resend';
    }

    protected static function forgotPasswordEndpoint(): string
    {
        return '/api/v1/auth/forgot-password';
    }
}
