<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class GetMerchantStatsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        $todayRedemptions = $this->getTodayRedemptions($merchant->id);
        $allTimeRevenue = $this->getAllTimeRevenue($merchant->id);
        $topPackages = $this->getTopPackages($merchant->id);

        return response()->json([
            'data' => [
                'todayRedemptions' => [
                    'count' => (int) $todayRedemptions->count,
                    'totalAmount' => (float) $todayRedemptions->total_amount,
                ],
                'allTimeRevenue' => (float) $allTimeRevenue,
                'topPackages' => $topPackages->map(fn (CreditPackage $package) => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'totalRevenue' => (float) ($package->total_revenue ?? 0),
                    'purchaseCount' => (int) $package->purchase_count,
                ]),
            ],
        ], Response::HTTP_OK);
    }

    private function getTodayRedemptions(string $merchantId): object
    {
        return CreditTransaction::query()
            ->where('merchant_id', $merchantId)
            ->where('type', TransactionType::CREDIT_REDEMPTION)
            ->where('status', TransactionStatus::COMPLETED)
            ->whereDate('redeemed_at', today())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount')
            ->first();
    }

    private function getAllTimeRevenue(string $merchantId): float
    {
        return (float) CreditTransaction::query()
            ->where('merchant_id', $merchantId)
            ->where('type', TransactionType::CREDIT_PURCHASE)
            ->where('status', TransactionStatus::COMPLETED)
            ->sum('paid_amount');
    }

    private function getTopPackages(string $merchantId): Collection
    {
        return CreditPackage::query()
            ->where('merchant_id', $merchantId)
            ->withCount(['transactions as purchase_count' => fn ($q) => $q
                ->where('type', TransactionType::CREDIT_PURCHASE)
                ->where('status', TransactionStatus::COMPLETED),
            ])
            ->withSum(['transactions as total_revenue' => fn ($q) => $q
                ->where('type', TransactionType::CREDIT_PURCHASE)
                ->where('status', TransactionStatus::COMPLETED),
            ], 'paid_amount')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();
    }
}
