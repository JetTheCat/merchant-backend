<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Enums\MerchantUserRole;
use App\Enums\MerchantUserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\CreateMerchantStaffRequest;
use App\Http\Resources\MerchantUserResource;
use App\Models\MerchantUser;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CreateMerchantStaffController extends Controller
{
    public function __invoke(CreateMerchantStaffRequest $request): JsonResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        $merchantUser = MerchantUser::create([
            'merchant_id' => $merchant->id,
            'name' => $request->validated('name'),
            'username' => $request->validated('username'),
            'password' => $request->validated('password'),
            'phone' => $request->validated('phone'),
            'role' => MerchantUserRole::STAFF,
            'status' => MerchantUserStatus::ACTIVE,
        ]);

        return response()->json([
            'message' => 'Merchant user created successfully.',
            'data' => new MerchantUserResource($merchantUser),
        ], Response::HTTP_CREATED);
    }
}
