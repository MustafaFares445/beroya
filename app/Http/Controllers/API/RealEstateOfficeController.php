<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRealEstateOfficeRequest;
use App\Http\Requests\UpdateRealEstateOfficeRequest;
use App\Http\Resources\RealEstateOfficeResource;
use App\Models\RealEstateOffice;
use App\Models\User;
use App\Services\RealEstateAccessService;
use App\Services\RealEstateOfficeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealEstateOfficeController extends Controller
{
    public function __construct(
        private readonly RealEstateOfficeService $realEstateOfficeService,
        private readonly RealEstateAccessService $realEstateAccessService
    ) {}

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
        $payload = $request->validated();

        if (! $this->canCreateRealEstateOffice($request, (int) $payload['province_id'])) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $realEstateOffice = $this->realEstateOfficeService->store($payload);

        return ApiResponse::success(RealEstateOfficeResource::make($realEstateOffice)->resolve());
    }

    public function update(UpdateRealEstateOfficeRequest $request, RealEstateOffice $realEstateOffice): JsonResponse
    {
        $payload = $request->validated();

        if (! $this->canUpdateRealEstateOffice($request, $realEstateOffice, (int) $payload['province_id'])) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedRealEstateOffice = $this->realEstateOfficeService->update($realEstateOffice, $payload);

        return ApiResponse::success(RealEstateOfficeResource::make($updatedRealEstateOffice)->resolve());
    }

    public function destroy(Request $request, RealEstateOffice $realEstateOffice): JsonResponse
    {
        if (! $this->canDeleteRealEstateOffice($request, $realEstateOffice)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $this->realEstateOfficeService->delete($realEstateOffice);

        return ApiResponse::success(['id' => $realEstateOffice->id]);
    }

    private function canCreateRealEstateOffice(Request $request, int $provinceId): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return $this->realEstateAccessService->canCreateOffice($user, $provinceId);
    }

    private function canUpdateRealEstateOffice(
        Request $request,
        RealEstateOffice $realEstateOffice,
        int $provinceId
    ): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return $this->realEstateAccessService->canUpdateOffice($user, $realEstateOffice, $provinceId);
    }

    private function canDeleteRealEstateOffice(Request $request, RealEstateOffice $realEstateOffice): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return $this->realEstateAccessService->canDeleteOffice($user, $realEstateOffice);
    }
}
