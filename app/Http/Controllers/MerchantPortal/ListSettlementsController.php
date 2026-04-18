<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Enums\SettlementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\ListSettlementsRequest;
use App\Http\Resources\SettlementResource;
use App\Http\Responses\CursorPaginationMeta;
use App\Models\Settlement;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ListSettlementsController extends Controller
{
    public function __invoke(ListSettlementsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $merchant = $request->user()->merchant()->firstOrFail();

        $query = Settlement::query()
            ->where('merchant_id', $merchant->id)
            ->orderByDesc('period_start')
            ->orderByDesc('id');

        if (isset($validated['status'])) {
            $query->where('status', SettlementStatus::from($validated['status']));
        }

        if (isset($validated['date_from'])) {
            $query->whereDate('period_start', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->whereDate('period_start', '<=', $validated['date_to']);
        }

        $settlements = $query->cursorPaginate($request->getLimit());

        return response()->json([
            'data' => SettlementResource::collection($settlements->items()),
            'meta' => CursorPaginationMeta::build($settlements, $request->getLimit()),
        ], Response::HTTP_OK);
    }
}
