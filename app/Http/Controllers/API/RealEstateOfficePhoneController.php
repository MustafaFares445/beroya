<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRealEstateOfficePhoneRequest;
use App\Http\Requests\UpdateRealEstateOfficePhoneRequest;
use App\Http\Resources\OfficePhoneResource;
use App\Models\RealEstateOfficePhone;
use App\Models\User;
use App\Services\RealEstateOfficePhoneService;
use App\Support\ApiResponse;
use App\Support\RealEstate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealEstateOfficePhoneController extends Controller
{
    public function __construct(private readonly RealEstateOfficePhoneService $realEstateOfficePhoneService) {}

    public function index(): JsonResponse
    {
        $phones = RealEstateOfficePhone::query()->get();

        return ApiResponse::success(OfficePhoneResource::collection($phones)->resolve());
    }

    public function show(RealEstateOfficePhone $realEstateOfficePhone): JsonResponse
    {
        return ApiResponse::success(OfficePhoneResource::make($realEstateOfficePhone)->resolve());
    }

    public function store(StoreRealEstateOfficePhoneRequest $request): JsonResponse
    {
        if (! $this->canCreateRealEstateOfficePhone($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $realEstateOfficePhone = $this->realEstateOfficePhoneService->store($request->validated());

        return ApiResponse::success(OfficePhoneResource::make($realEstateOfficePhone)->resolve());
    }

    public function update(UpdateRealEstateOfficePhoneRequest $request, RealEstateOfficePhone $realEstateOfficePhone): JsonResponse
    {
        if (! $this->canManageRealEstateOfficePhone($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedRealEstateOfficePhone = $this->realEstateOfficePhoneService->update(
            $realEstateOfficePhone,
            $request->validated()
        );

        return ApiResponse::success(OfficePhoneResource::make($updatedRealEstateOfficePhone)->resolve());
    }

    public function destroy(Request $request, RealEstateOfficePhone $realEstateOfficePhone): JsonResponse
    {
        if (! $this->canManageRealEstateOfficePhone($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $this->realEstateOfficePhoneService->delete($realEstateOfficePhone);

        return ApiResponse::success();
    }

    private function canCreateRealEstateOfficePhone(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return RealEstate::canCreateLookupData($user);
    }

    private function canManageRealEstateOfficePhone(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return RealEstate::canManageLookupData($user);
    }
}
