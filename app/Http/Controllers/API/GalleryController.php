<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGalleryRequest;
use App\Http\Requests\UpdateGalleryRequest;
use App\Http\Resources\GalleryResource;
use App\Models\Gallery;
use App\Models\User;
use App\Services\GalleryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function __construct(private readonly GalleryService $galleryService)
    {
    }

    public function index(): JsonResponse
    {
        $galleries = Gallery::query()->get();

        return ApiResponse::success(GalleryResource::collection($galleries)->resolve());
    }

    public function store(StoreGalleryRequest $request): JsonResponse
    {
        if (!$this->canManageGalleries($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $gallery = $this->galleryService->store($request->validated());

        return ApiResponse::success(GalleryResource::make($gallery)->resolve());
    }

    public function show(Gallery $gallery): JsonResponse
    {
        return ApiResponse::success(GalleryResource::make($gallery)->resolve());
    }

    public function update(UpdateGalleryRequest $request, Gallery $gallery): JsonResponse
    {
        if (!$this->canManageGalleries($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedGallery = $this->galleryService->update($gallery, $request->validated());

        return ApiResponse::success(GalleryResource::make($updatedGallery)->resolve());
    }

    public function destroy(Request $request, Gallery $gallery): JsonResponse
    {
        if (!$this->canManageGalleries($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $this->galleryService->delete($gallery);

        return ApiResponse::success();
    }

    private function canManageGalleries(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return (int) $user->permetions_level === 1;
    }
}

