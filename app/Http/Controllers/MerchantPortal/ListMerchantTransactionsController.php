<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\ListMerchantTransactionsRequest;
use App\Http\Resources\MerchantTransactionResource;
use App\Http\Responses\CursorPaginationMeta;
use App\Models\CreditTransaction;
use App\Models\MerchantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ListMerchantTransactionsController extends Controller
{
    public function __invoke(ListMerchantTransactionsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $merchant = $request->user()->merchant()->firstOrFail();

        $query = CreditTransaction::query()
            ->with('merchantUser')
            ->where('merchant_id', $merchant->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (isset($validated['type'])) {
            $query->where('type', TransactionType::from($validated['type']));
        }

        if (isset($validated['merchant_user_id'])) {
            if (! MerchantUser::where('id', $validated['merchant_user_id'])->where('merchant_id', $merchant->id)->exists()) {
                throw ValidationException::withMessages([
                    'merchant_user_id' => ['The selected merchant user does not belong to this merchant.'],
                ]);
            }

            $query->where('merchant_user_id', $validated['merchant_user_id']);
        }

        $transactions = $query->cursorPaginate($request->getLimit());

        return response()->json([
            'data' => MerchantTransactionResource::collection($transactions->items()),
            'meta' => CursorPaginationMeta::build($transactions, $request->getLimit()),
        ], Response::HTTP_OK);
    }
}
