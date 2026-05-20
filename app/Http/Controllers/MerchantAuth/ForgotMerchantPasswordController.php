<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantAuth;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantAuth\ForgotMerchantPasswordRequest;
use App\Jobs\SendMerchantUserPasswordResetNotificationJob;
use App\Models\MerchantUser;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ForgotMerchantPasswordController extends Controller
{
    public function __invoke(ForgotMerchantPasswordRequest $request): JsonResponse
    {
        $merchantUser = MerchantUser::where('email', $request->input('email'))->first();

        if ($merchantUser && $merchantUser->isOwner()) {
            SendMerchantUserPasswordResetNotificationJob::dispatch($merchantUser);
        }

        return response()->json([
            'message' => 'If an account exists with that email, a password reset link has been sent.',
        ], Response::HTTP_OK);
    }
}
