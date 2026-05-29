<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\ListMerchantLocationsRequest;
use App\Http\Resources\MerchantLocationResource;
use App\Models\MerchantLocation;
use Illuminate\Http\JsonResponse;

class ListMerchantLocationsController extends Controller
{
    public function __invoke(ListMerchantLocationsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $locations = MerchantLocation::where('merchant_id', $request->user()->merchant_id)
            ->when(isset($validated['is_active']), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when(isset($validated['state']), fn ($q) => $q->where('state', $validated['state']))
            ->when(isset($validated['name']), fn ($q) => $q->where('name', 'ilike', '%'.$validated['name'].'%'))
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => MerchantLocationResource::collection($locations),
        ]);
    }
}
