<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettlementResource;
use App\Models\Settlement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GetSettlementController extends Controller
{
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        $settlement = Settlement::where('merchant_id', $merchant->id)
            ->findOrFail($id);

        return response()->json([
            'data' => new SettlementResource($settlement),
        ], Response::HTTP_OK);
    }
}
