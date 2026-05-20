<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Enums\MerchantUserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemoveMerchantStaffController extends Controller
{
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        $merchantUser = $merchant->merchantUsers()->where('role', MerchantUserRole::STAFF)->find($id);

        if (! $merchantUser) {
            return response()->json(['message' => 'Merchant user not found.'], Response::HTTP_NOT_FOUND);
        }

        $merchantUser->delete();

        return response()->json(['message' => 'Merchant user removed successfully.'], Response::HTTP_OK);
    }
}
