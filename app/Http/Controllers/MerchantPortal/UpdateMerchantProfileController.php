<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\UpdateMerchantProfileRequest;
use App\Http\Resources\MerchantResource;
use Illuminate\Http\JsonResponse;

class UpdateMerchantProfileController extends Controller
{
    public function __invoke(UpdateMerchantProfileRequest $request): JsonResponse
    {
        $merchant = $request->user()->merchant()->with('category')->firstOrFail();

        $merchant->update($request->validated());

        return response()->json([
            'message' => 'Merchant profile updated successfully',
            'data' => new MerchantResource($merchant->fresh('category')),
        ]);
    }
}
