<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyCategoryResource;
use App\Models\PropertyCategory;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PropertyCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $propertyCategories = PropertyCategory::query()->get();

        return ApiResponse::success(PropertyCategoryResource::collection($propertyCategories)->resolve());
    }
}
