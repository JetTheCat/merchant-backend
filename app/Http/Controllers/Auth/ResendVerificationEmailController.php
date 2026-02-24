<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailVerificationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResendVerificationEmailController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ], Response::HTTP_OK);
        }

        SendEmailVerificationJob::dispatch($request->user());

        return response()->json([
            'message' => 'Verification email sent.',
        ], Response::HTTP_OK);
    }
}
