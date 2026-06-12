<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\RealEstate;
use Illuminate\Http\JsonResponse;

class RealEstateOptionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(RealEstate::optionGroups());
    }
}
