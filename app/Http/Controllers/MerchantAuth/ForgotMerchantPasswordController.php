<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ForgotMerchantPasswordController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([]);
    }
}
