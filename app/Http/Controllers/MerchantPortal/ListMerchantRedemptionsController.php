<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\ListMerchantRedemptionsRequest;
use App\Http\Resources\MerchantTransactionResource;
use App\Http\Responses\CursorPaginationMeta;
use App\Models\CreditTransaction;
use App\Models\MerchantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ListMerchantRedemptionsController extends Controller
{
    public function __invoke(ListMerchantRedemptionsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $merchant = $request->user()->merchant()->firstOrFail();

        $query = CreditTransaction::query()
            ->with('merchantUser')
            ->where('merchant_id', $merchant->id)
            ->where('type', TransactionType::CREDIT_REDEMPTION)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (isset($validated['merchant_user_id'])) {
            $validMerchantUser = MerchantUser::where('id', $validated['merchant_user_id'])
                ->where('merchant_id', $merchant->id)
                ->exists();
    
            if (! $validMerchantUser) {
                throw ValidationException::withMessages([
                    'merchant_user_id' => ['The selected merchant user does not belong to this merchant.'],
                ]);
            }

            $query->where('merchant_user_id', $validated['merchant_user_id']);
        }

        if (isset($validated['status'])) {
            $query->where('status', TransactionStatus::from($validated['status']));
        }

        if (isset($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $redemptions = $query->cursorPaginate($request->getLimit());

        return response()->json([
            'data' => MerchantTransactionResource::collection($redemptions->items()),
            'meta' => CursorPaginationMeta::build($redemptions, $request->getLimit()),
        ], Response::HTTP_OK);
    }
}
