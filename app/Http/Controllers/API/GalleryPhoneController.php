<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGalleryPhoneRequest;
use App\Http\Requests\UpdateGalleryPhoneRequest;
use App\Http\Resources\GalleryPhoneResource;
use App\Models\GalleryPhone;
use App\Models\User;
use App\Services\GalleryPhoneService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryPhoneController extends Controller
{
    public function __construct(private readonly GalleryPhoneService $galleryPhoneService) {}

    public function index(): JsonResponse
    {
        $phones = GalleryPhone::query()->get();

        return ApiResponse::success(GalleryPhoneResource::collection($phones)->resolve());
    }

    public function store(StoreGalleryPhoneRequest $request): JsonResponse
    {
        if (! $this->canManagePhones($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $galleryPhone = $this->galleryPhoneService->store($request->validated());

        return ApiResponse::success(GalleryPhoneResource::make($galleryPhone)->resolve());
    }

    public function show(GalleryPhone $galleryPhone): JsonResponse
    {
        return ApiResponse::success(GalleryPhoneResource::make($galleryPhone)->resolve());
    }

    public function update(UpdateGalleryPhoneRequest $request, GalleryPhone $galleryPhone): JsonResponse
    {
        if (! $this->canManagePhones($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedGalleryPhone = $this->galleryPhoneService->update($galleryPhone, $request->validated());

        return ApiResponse::success(GalleryPhoneResource::make($updatedGalleryPhone)->resolve());
    }

    public function destroy(Request $request, GalleryPhone $galleryPhone): JsonResponse
    {
        if (! $this->canManagePhones($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $this->galleryPhoneService->delete($galleryPhone);

        return ApiResponse::success(['id' => $galleryPhone->id]);
    }

    private function canManagePhones(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return in_array((int) $user->permetions_level, [1, 2], true);
    }
}
