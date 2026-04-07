<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserCreditResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetUserCreditController extends Controller
{
    public function __invoke(Request $request, string $merchantId): JsonResponse
    {
        $credit = $request->user()
            ->userCredits()
            ->with('merchant')
            ->byMerchant($merchantId)
            ->firstOrFail();

        return response()->json([
            'data' => new UserCreditResource($credit),
        ]);
    }
}
