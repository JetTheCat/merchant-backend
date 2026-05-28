<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\UpdateMerchantLocationRequest;
use App\Http\Resources\MerchantLocationResource;
use App\Models\MerchantLocation;
use Illuminate\Http\JsonResponse;

class UpdateMerchantLocationController extends Controller
{
    public function __invoke(UpdateMerchantLocationRequest $request, string $id): JsonResponse
    {
        $location = MerchantLocation::where('merchant_id', $request->user()->merchant_id)
            ->findOrFail($id);

        $location->update($request->validated());

        return response()->json([
            'message' => 'Location updated successfully.',
            'data' => new MerchantLocationResource($location->fresh()),
        ]);
    }
}
