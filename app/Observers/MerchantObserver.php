<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Merchant;
use Illuminate\Support\Facades\DB;

class MerchantObserver
{
    /**
     * Handle the merchant "saving" event.
     */
    public function saving(Merchant $merchant): void
    {
        if ($merchant->latitude && $merchant->longitude) {
            $merchant->location = DB::raw(sprintf(
                "ST_SetSRID(ST_MakePoint(%s, %s), 4326)",
                $merchant->longitude,
                $merchant->latitude
            ));
        }
    }
}
