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
        $redemptions = $settlement->transactions()
            ->where('type', TransactionType::CREDIT_REDEMPTION)
            ->orderBy('created_at')
            ->get(['created_at', 'reference_code', 'settlement_amount']);

        $filename = "settlement-{$settlement->reference_code}.pdf";

        return Pdf::view('settlements.invoice', [
            'settlement' => $settlement->load('merchant'),
            'redemptions' => $redemptions,
        ])->download($filename);
    }
}
