<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexPropertyRequest;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Models\User;
use App\Services\RealEstateAccessService;
use App\Services\PropertyService;
use App\Support\ApiResponse;
use App\Support\SanctumUserResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService $propertyService,
        private readonly RealEstateAccessService $realEstateAccessService
    ) {}

    public function index(IndexPropertyRequest $request): JsonResponse
    {
        $properties = $this->propertyService->list($request->validated());
        $viewer = SanctumUserResolver::fromRequest($request);
        $propertyData = PropertyResource::collection($properties)->resolve();

        return ApiResponse::success($this->resolvePropertyData($propertyData, $viewer));
    }

    public function show(Request $request, Property $property): JsonResponse
    {
        $property->loadMissing([
            'province',
            'office.province',
            'mainCategory',
            'subcategory',
            'images',
        ]);

        $viewer = SanctumUserResolver::fromRequest($request);

        return ApiResponse::success($this->sanitizePropertyData(
            PropertyResource::make($property)->resolve(),
            $viewer
        ));
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        if (! $this->canManageProperties($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $property = $this->propertyService->store($request->validated());

        return ApiResponse::success(PropertyResource::make($property)->resolve());
    }

    public function update(UpdatePropertyRequest $request, Property $property): JsonResponse
    {
        if (! $this->canManageProperties($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedProperty = $this->propertyService->update($property, $request->validated());

        return ApiResponse::success(PropertyResource::make($updatedProperty)->resolve());
    }

    public function destroy(Request $request, Property $property): JsonResponse
    {
        if (! $this->canManageProperties($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $this->propertyService->delete($property);

        return ApiResponse::success(['id' => $property->id]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $properties
     * @return array<int, array<string, mixed>>
     */
    private function resolvePropertyData(array $properties, ?User $viewer): array
    {
        if ($this->canViewSensitivePropertyData($viewer)) {
            return $properties;
        }

        return array_map(function (array $property): array {
            return $this->sanitizePropertyData($property);
        }, $properties);
    }

    /**
     * @param  array<string, mixed>  $propertyData
     * @return array<string, mixed>
     */
    private function sanitizePropertyData(array $propertyData, ?User $viewer = null): array
    {
        if ($this->canViewSensitivePropertyData($viewer)) {
            return $propertyData;
        }

        $propertyData['owner_name'] = '';
        $propertyData['owner_phone'] = '';

        return $propertyData;
    }

    private function canManageProperties(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return $this->realEstateAccessService->canManageProperties($user);
    }

    private function canViewSensitivePropertyData(?User $user): bool
    {
        return $user !== null && $this->realEstateAccessService->canManageProperties($user);
    }
}
