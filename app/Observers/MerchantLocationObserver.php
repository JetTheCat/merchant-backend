<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\MerchantLocation;
use Illuminate\Support\Str;

class MerchantLocationObserver
{
    public function creating(MerchantLocation $location): void
    {
        if (! isset($location->redemption_code)) {
            $location->redemption_code = Str::uuid()->toString();
        }
    }
}
