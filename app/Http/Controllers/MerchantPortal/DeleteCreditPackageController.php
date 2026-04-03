<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\DeleteCreditPackageRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DeleteCreditPackageController extends Controller
{
    public function __invoke(DeleteCreditPackageRequest $request, string $id): JsonResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        $merchant->creditPackages()->findOrFail($id)->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
