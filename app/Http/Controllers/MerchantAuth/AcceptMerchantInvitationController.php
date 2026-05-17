<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantAuth;

use App\Enums\MerchantUserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantAuth\AcceptMerchantInvitationRequest;
use App\Models\MerchantUser;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AcceptMerchantInvitationController extends Controller
{
    public function __invoke(AcceptMerchantInvitationRequest $request, string $id, string $hash): JsonResponse
    {
        $merchantUser = MerchantUser::find($id);

        if (! $merchantUser || ! $merchantUser->isOwner() || sha1($merchantUser->email) !== $hash) {
            return response()->json([
                'message' => 'Invalid or expired invitation link.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (! $merchantUser->isPendingVerification()) {
            return response()->json([
                'message' => 'This invitation has already been accepted.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $merchantUser->update([
            'password' => $request->input('password'),
            'status' => MerchantUserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Account set up successfully.',
        ], Response::HTTP_OK);
    }
}
