<?php

declare(strict_types=1);

use App\Models\MerchantUser;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('merchant-location.{merchantLocationId}', function (MerchantUser $user, string $merchantLocationId) {
    return $user->merchant_location_id === $merchantLocationId;
});
