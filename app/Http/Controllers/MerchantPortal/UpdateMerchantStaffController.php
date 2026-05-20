<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Enums\MerchantUserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\UpdateMerchantStaffRequest;
use App\Http\Resources\MerchantUserResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UpdateMerchantStaffController extends Controller
{
    public function __invoke(UpdateMerchantStaffRequest $request, string $id): JsonResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        $merchantUser = $merchant->merchantUsers()->where('role', MerchantUserRole::STAFF)->find($id);

        if (! $merchantUser) {
            return response()->json(['message' => 'Merchant user not found.'], Response::HTTP_NOT_FOUND);
        }

        $merchantUser->update($request->validated());

        return response()->json([
            'message' => 'Merchant user updated successfully.',
            'data' => new MerchantUserResource($merchantUser->fresh()),
        ]);
    }
}
