<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class GetUserController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(
            new UserResource(request()->user())
        );
    }
}
