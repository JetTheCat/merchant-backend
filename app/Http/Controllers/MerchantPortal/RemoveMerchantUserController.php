<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemoveMerchantUserController extends Controller
{
    public function __invoke(Request $request, string $id): JsonResponse
    {
        if (! $request->user()->isOwner()) {
            return response()->json(['message' => 'Only the merchant owner can remove users.'], Response::HTTP_FORBIDDEN);
        }

        $merchant = $request->user()->merchant()->firstOrFail();

        $merchantUser = $merchant->merchantUsers()->find($id);

        if (! $merchantUser) {
            return response()->json(['message' => 'Merchant user not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($merchantUser->isOwner()) {
            return response()->json(['message' => 'Cannot remove the owner account.'], Response::HTTP_FORBIDDEN);
        }

        $merchantUser->delete();

        return response()->json(['message' => 'Merchant user removed successfully.'], Response::HTTP_OK);
    }
}
