<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\ListMerchantTransactionsRequest;
use App\Http\Resources\MerchantTransactionResource;
use App\Http\Responses\CursorPaginationMeta;
use App\Models\CreditTransaction;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ListMerchantTransactionsController extends Controller
{
    public function __invoke(ListMerchantTransactionsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $merchantId = $request->user()->merchant_id;

        $query = CreditTransaction::query()
            ->with([
                'merchantUser',
                'user'
            ])
            ->where('merchant_id', $merchantId)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (isset($validated['type'])) {
            $query->where('type', TransactionType::from($validated['type']));
        }

        if (isset($validated['status'])) {
            $query->where('status', TransactionStatus::from($validated['status']));
        }

        if (isset($validated['reference'])) {
            $query->where('reference_code', 'ilike', '%'.$validated['reference'].'%');
        }

        if (isset($validated['merchant_user_id'])) {
            $query->where('merchant_user_id', $validated['merchant_user_id']);
        }

        if ($from = $request->parseDate('date_from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->parseDate('date_to')) {
            $query->where('created_at', '<=', $to);
        }

        if (isset($validated['merchant_location_id'])) {
            $query->where('merchant_location_id', $validated['merchant_location_id']);
        }

        $transactions = $query->cursorPaginate($request->getLimit());

        return response()->json([
            'data' => MerchantTransactionResource::collection($transactions->items()),
            'meta' => CursorPaginationMeta::build($transactions, $request->getLimit()),
        ], Response::HTTP_OK);
    }
}
