<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Jobs\SendPasswordResetNotificationJob;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ForgotPasswordController extends Controller
{
    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if ($user) {
            SendPasswordResetNotificationJob::dispatch($user);
        }

        return response()->json([
            'message' => 'If an account exists with that email, a password reset link has been sent.',
        ], Response::HTTP_OK);
    }
}
