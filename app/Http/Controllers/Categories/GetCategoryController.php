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

class GetCategoryController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $cacheKey = "category.{$id}";

        $category = Cache::tags([CacheTags::CATEGORIES])->get($cacheKey);

        if (! $category) {
            $category = Category::find($id);

            if (! $category) {
                return response()->json([
                    'message' => 'Category does not exist.',
                ], Response::HTTP_NOT_FOUND);
            }

            Cache::tags([CacheTags::CATEGORIES])->put($cacheKey, $category, 3600);
        }

        return response()->json(
            [
                'data' => new CategoryResource($category),
            ],
            Response::HTTP_OK
        );
    }
}
