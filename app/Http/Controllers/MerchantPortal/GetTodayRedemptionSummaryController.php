<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\CreditTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GetTodayRedemptionSummaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        $summary = CreditTransaction::query()
            ->where('merchant_id', $merchant->id)
            ->where('type', TransactionType::CREDIT_REDEMPTION)
            ->where('status', TransactionStatus::COMPLETED)
            ->whereDate('redeemed_at', today())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount')
            ->first();
        
        return response()->json([
            'data' => [
                'totalAmount' => (float) $summary->total_amount,
                'count' => (int) $summary->count,
            ],
        ], Response::HTTP_OK);
    }
}
