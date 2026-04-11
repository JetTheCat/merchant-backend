<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GetPaymentController extends Controller
{
    public function __invoke(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'data' => new PaymentResource($payment),
        ]);
    }
}
