<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertySubcategoryResource;
use App\Models\PropertySubcategory;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PropertySubcategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $propertySubcategories = PropertySubcategory::query()->get();

        return ApiResponse::success(PropertySubcategoryResource::collection($propertySubcategories)->resolve());
    }
}
