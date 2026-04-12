<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditTransactionResource;
use App\Models\CreditTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GetCreditTransactionController extends Controller
{
    public function __invoke(Request $request, CreditTransaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $transaction->load('merchant');

        return response()->json([
            'data' => new CreditTransactionResource($transaction),
        ]);
    }
}
