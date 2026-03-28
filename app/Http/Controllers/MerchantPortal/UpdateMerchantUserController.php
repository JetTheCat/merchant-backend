<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\UpdateMerchantUserRequest;
use App\Http\Resources\MerchantUserResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UpdateMerchantUserController extends Controller
{
    public function __invoke(UpdateMerchantUserRequest $request, string $id): JsonResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        $merchantUser = $merchant->merchantUsers()->find($id);

        if (! $merchantUser) {
            return response()->json(['message' => 'Merchant user not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($merchantUser->isOwner()) {
            return response()->json(['message' => 'Cannot modify the owner account.'], Response::HTTP_FORBIDDEN);
        }

        $merchantUser->update($request->validated());

        return response()->json([
            'message' => 'Merchant user updated successfully.',
            'data' => new MerchantUserResource($merchantUser->fresh()),
        ]);
    }
}
