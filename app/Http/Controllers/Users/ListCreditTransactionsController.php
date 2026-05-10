<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\ListCreditTransactionsRequest;
use App\Http\Resources\CreditTransactionResource;
use App\Http\Responses\CursorPaginationMeta;
use App\Models\CreditTransaction;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ListCreditTransactionsController extends Controller
{
    public function __invoke(ListCreditTransactionsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = CreditTransaction::query()
            ->with('merchant')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (isset($validated['merchant_id'])) {
            $query->where('merchant_id', $validated['merchant_id']);
        }

        if (isset($validated['type'])) {
            $query->where('type', TransactionType::from($validated['type']));
        }

        if (isset($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $transactions = $query->cursorPaginate($request->getLimit());

        return response()->json([
            'data' => CreditTransactionResource::collection($transactions->items()),
            'meta' => CursorPaginationMeta::build($transactions, $request->getLimit()),
        ], Response::HTTP_OK);
    }
}
