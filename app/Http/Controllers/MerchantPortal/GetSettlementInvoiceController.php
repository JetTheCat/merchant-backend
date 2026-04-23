<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Settlement;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\Facades\Pdf;

class GetSettlementInvoiceController extends Controller
{
    public function __invoke(Request $request, string $id): mixed
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        $settlement = Settlement::where('merchant_id', $merchant->id)
            ->with('merchant')
            ->findOrFail($id);

        $purchases = $settlement->transactions()
            ->where('type', TransactionType::CREDIT_PURCHASE)
            ->orderBy('created_at')
            ->get(['created_at', 'reference_code', 'paid_amount', 'platform_fee']);

        $filename = "settlement-{$settlement->reference_code}.pdf";

        return Pdf::view('settlements.invoice', [
            'settlement' => $settlement,
            'purchases' => $purchases,
        ])->download($filename);
    }
}
