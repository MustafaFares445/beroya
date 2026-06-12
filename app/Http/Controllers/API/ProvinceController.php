<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProvinceRequest;
use App\Http\Requests\UpdateProvinceRequest;
use App\Http\Resources\ProvinceResource;
use App\Models\Province;
use App\Models\User;
use App\Services\ProvinceService;
use App\Support\ApiResponse;
use App\Support\RealEstate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    public function __construct(private readonly ProvinceService $provinceService) {}

    public function index(): JsonResponse
    {
        $provinces = Province::query()->get();

        return ApiResponse::success(ProvinceResource::collection($provinces)->resolve());
    }

    public function show(Province $province): JsonResponse
    {
        return ApiResponse::success(ProvinceResource::make($province)->resolve());
    }

    public function store(StoreProvinceRequest $request): JsonResponse
    {
        if (! $this->canCreateProvince($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $province = $this->provinceService->store($request->validated());

        return ApiResponse::success(ProvinceResource::make($province)->resolve());
    }

    public function update(UpdateProvinceRequest $request, Province $province): JsonResponse
    {
        if (! $this->canManageProvince($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedProvince = $this->provinceService->update($province, $request->validated());

        return ApiResponse::success(ProvinceResource::make($updatedProvince)->resolve());
    }

    public function destroy(Request $request, Province $province): JsonResponse
    {
        if (! $this->canManageProvince($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $this->provinceService->delete($province);

        return ApiResponse::success();
    }

    private function canCreateProvince(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return RealEstate::canCreateLookupData($user);
    }

    private function canManageProvince(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return RealEstate::canManageLookupData($user);
    }
}
