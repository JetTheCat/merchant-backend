<?php

declare(strict_types=1);

namespace Tests\Api\v1\Traits;

trait MerchantEndpoints
{
    protected static function listMerchantsEndpoint(array $params = []): string
    {
        $url = '/api/v1/merchants';
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    protected static function getMerchantEndpoint(string $merchantId): string
    {
        return sprintf(
            '/api/v1/merchants/%s',
            $merchantId
        );
    }
}
