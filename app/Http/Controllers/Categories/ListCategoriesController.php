<?php

declare(strict_types=1);

namespace App\Http\Controllers\Categories;

use App\Constants\CacheTags;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ListCategoriesController extends Controller
{
    private const CACHE_KEY = 'categories.all';

    public function __invoke(): JsonResponse
    {
        $categories = Cache::tags([CacheTags::CATEGORIES])->get(self::CACHE_KEY);

        if (! $categories) {
            $categories = Category::active()->ordered()->get();
            Cache::tags([CacheTags::CATEGORIES])->put(self::CACHE_KEY, $categories, 3600);
        }

        return response()->json(
            [
                'data' => CategoryResource::collection($categories),
            ],
            Response::HTTP_OK
        );
    }
}
