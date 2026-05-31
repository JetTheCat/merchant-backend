<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\UpdateMerchantOwnerRequest;
use App\Http\Resources\MerchantUserResource;
use Illuminate\Http\JsonResponse;

class UpdateMerchantOwnerController extends Controller
{
    public function __invoke(UpdateMerchantOwnerRequest $request): JsonResponse
    {
        $owner = $request->user();

        $owner->update($request->validated());

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => new MerchantUserResource($owner->fresh()),
        ]);
    }
}
