<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MerchantLocation;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MerchantLocationService
{
    public function remove(MerchantLocation $location): void
    {
        if ($location->is_active) {
            throw new InvalidArgumentException('Location must be deactivated before it can be removed.');
        }

        DB::transaction(function () use ($location): void {
            $location->merchantUsers()->update(['merchant_location_id' => null]);

            if (Transaction::where('merchant_location_id', $location->id)->exists()) {
                $location->delete();
            } else {
                $location->forceDelete();
            }
        });
    }
}
