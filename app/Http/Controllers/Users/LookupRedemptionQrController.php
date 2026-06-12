<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\LookupRedemptionQrRequest;
use App\Models\MerchantLocation;
use App\Models\UserCredit;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LookupRedemptionQrController extends Controller
{
    public function __invoke(LookupRedemptionQrRequest $request): JsonResponse
    {
        $location = MerchantLocation::with('merchant')
            ->where('redemption_code', $request->validated('code'))
            ->where('is_active', true)
            ->first();

        if ($location === null) {
            return response()->json(['message' => 'Invalid QR code.'], Response::HTTP_NOT_FOUND);
        }

        $userCredit = UserCredit::where('user_id', $request->user()->id)
            ->where('merchant_id', $location->merchant_id)
            ->first();

        return response()->json([
            'data' => [
                'merchantId' => $location->merchant_id,
                'merchantName' => $location->merchant->name,
                'locationId' => $location->id,
                'locationName' => $location->name,
                'availableBalance' => $userCredit?->balance ?? '0.00',
            ],
        ]);
    }
}
