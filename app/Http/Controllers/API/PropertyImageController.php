<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyImagesRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use App\Services\PropertyService;
use App\Support\ApiResponse;
use App\Support\RealEstate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyImageController extends Controller
{
    public function __construct(private readonly PropertyService $propertyService) {}

    public function store(StorePropertyImagesRequest $request, Property $property): JsonResponse
    {
        if (! $this->canManageProperties($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedProperty = $this->propertyService->addImages($property, $request->validated());

        return ApiResponse::success(PropertyResource::make($updatedProperty)->resolve());
    }

    public function destroy(Request $request, PropertyImage $image): JsonResponse
    {
        if (! $this->canManageProperties($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedProperty = $this->propertyService->deleteImage($image);

        return ApiResponse::success(PropertyResource::make($updatedProperty)->resolve());
    }

    private function canManageProperties(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return RealEstate::canManageProperties($user);
    }
}
