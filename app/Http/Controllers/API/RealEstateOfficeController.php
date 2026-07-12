<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRealEstateOfficeRequest;
use App\Http\Requests\UpdateRealEstateOfficeRequest;
use App\Http\Resources\RealEstateOfficeResource;
use App\Models\RealEstateOffice;
use App\Models\User;
use App\Services\RealEstateOfficeService;
use App\Support\ApiResponse;
use App\Support\RealEstate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealEstateOfficeController extends Controller
{
    public function __construct(private readonly RealEstateOfficeService $realEstateOfficeService) {}

    public function index(): JsonResponse
    {
        $offices = RealEstateOffice::query()->with('province')->get();

        return ApiResponse::success(RealEstateOfficeResource::collection($offices)->resolve());
    }

    public function show(RealEstateOffice $realEstateOffice): JsonResponse
    {
        return ApiResponse::success(
            RealEstateOfficeResource::make($realEstateOffice->loadMissing('province'))->resolve()
        );
    }

    public function store(StoreRealEstateOfficeRequest $request): JsonResponse
    {
        if (! $this->canCreateRealEstateOffice($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $realEstateOffice = $this->realEstateOfficeService->store($request->validated());

        return ApiResponse::success(RealEstateOfficeResource::make($realEstateOffice)->resolve());
    }

    public function update(UpdateRealEstateOfficeRequest $request, RealEstateOffice $realEstateOffice): JsonResponse
    {
        if (! $this->canManageRealEstateOffice($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedRealEstateOffice = $this->realEstateOfficeService->update($realEstateOffice, $request->validated());

        return ApiResponse::success(RealEstateOfficeResource::make($updatedRealEstateOffice)->resolve());
    }

    public function destroy(Request $request, RealEstateOffice $realEstateOffice): JsonResponse
    {
        if (! $this->canManageRealEstateOffice($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $this->realEstateOfficeService->delete($realEstateOffice);

        return ApiResponse::success(['id' => $realEstateOffice->id]);
    }

    private function canCreateRealEstateOffice(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return RealEstate::canCreateLookupData($user);
    }

    private function canManageRealEstateOffice(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return RealEstate::canManageLookupData($user);
    }
}
