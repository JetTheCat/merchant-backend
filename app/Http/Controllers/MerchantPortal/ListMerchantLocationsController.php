<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantLocationResource;
use App\Models\MerchantLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMerchantLocationsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $locations = MerchantLocation::where('merchant_id', $request->user()->merchant_id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => MerchantLocationResource::collection($locations),
        ]);
    }
}
