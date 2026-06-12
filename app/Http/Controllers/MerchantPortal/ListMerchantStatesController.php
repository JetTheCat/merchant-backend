<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Constants\CacheTags;
use App\Enums\MalaysianState;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ListMerchantStatesController extends Controller
{
    private const string CACHE_KEY = 'states.all';

    public function __invoke(): JsonResponse
    {
        $states = Cache::tags([CacheTags::STATES])->remember(
            self::CACHE_KEY,
            3600,
            MalaysianState::values(...)
        );

        return response()->json(['data' => $states]);
    }
}
