<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRealEstateOfficePhoneRequest;
use App\Http\Requests\UpdateRealEstateOfficePhoneRequest;
use App\Http\Resources\OfficePhoneResource;
use App\Models\RealEstateOfficePhone;
use App\Models\User;
use App\Services\RealEstateAccessService;
use App\Services\RealEstateOfficePhoneService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealEstateOfficePhoneController extends Controller
{
    public function __construct(
        private readonly RealEstateOfficePhoneService $realEstateOfficePhoneService,
        private readonly RealEstateAccessService $realEstateAccessService
    ) {}

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
        $payload = $request->validated();

        if (! $this->canCreateRealEstateOfficePhone($request, (int) $payload['real_estate_office_id'])) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $realEstateOfficePhone = $this->realEstateOfficePhoneService->store($payload);

        return ApiResponse::success(OfficePhoneResource::make($realEstateOfficePhone)->resolve());
    }

    public function update(UpdateRealEstateOfficePhoneRequest $request, RealEstateOfficePhone $realEstateOfficePhone): JsonResponse
    {
        $payload = $request->validated();

        if (! $this->canUpdateRealEstateOfficePhone(
            $request,
            $realEstateOfficePhone,
            (int) $payload['real_estate_office_id']
        )) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedRealEstateOfficePhone = $this->realEstateOfficePhoneService->update(
            $realEstateOfficePhone,
            $payload
        );

        return ApiResponse::success(OfficePhoneResource::make($updatedRealEstateOfficePhone)->resolve());
    }

    public function destroy(Request $request, RealEstateOfficePhone $realEstateOfficePhone): JsonResponse
    {
        if (! $this->canDeleteRealEstateOfficePhone($request, $realEstateOfficePhone)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $this->realEstateOfficePhoneService->delete($realEstateOfficePhone);

        return ApiResponse::success(['id' => $realEstateOfficePhone->id]);
    }

    private function canCreateRealEstateOfficePhone(Request $request, int $officeId): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return $this->realEstateAccessService->canCreateOfficePhone($user, $officeId);
    }

    private function canUpdateRealEstateOfficePhone(
        Request $request,
        RealEstateOfficePhone $realEstateOfficePhone,
        int $officeId
    ): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return $this->realEstateAccessService->canUpdateOfficePhone($user, $realEstateOfficePhone, $officeId);
    }

    private function canDeleteRealEstateOfficePhone(
        Request $request,
        RealEstateOfficePhone $realEstateOfficePhone
    ): bool {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return $this->realEstateAccessService->canDeleteOfficePhone($user, $realEstateOfficePhone);
    }
}
