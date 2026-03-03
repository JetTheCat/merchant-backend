<?php

declare(strict_types=1);

namespace Tests\Api\v1\Traits;

trait CategoryEndpoints
{
    protected static function listCategoriesEndpoint(): string
    {
        return '/api/v1/categories';
    }

    protected static function getCategoryEndpoint(string $categoryId): string
    {
        return sprintf(
            '/api/v1/categories/%s',
            $categoryId
        );
    }
}
