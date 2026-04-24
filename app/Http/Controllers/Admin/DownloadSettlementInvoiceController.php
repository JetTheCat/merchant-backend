<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Settlement;
use Spatie\LaravelPdf\Facades\Pdf;

class DownloadSettlementInvoiceController extends Controller
{
    public function __invoke(Settlement $settlement): mixed
    {
        $purchases = $settlement->transactions()
            ->where('type', TransactionType::CREDIT_PURCHASE)
            ->orderBy('created_at')
            ->get(['created_at', 'reference_code', 'paid_amount', 'platform_fee']);

        $filename = "settlement-{$settlement->reference_code}.pdf";

        return Pdf::view('settlements.invoice', [
            'settlement' => $settlement->load('merchant'),
            'purchases' => $purchases,
        ])->download($filename);
    }
}
