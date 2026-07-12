<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarModelRequest;
use App\Http\Requests\UpdateCarModelRequest;
use App\Http\Resources\CarModelResource;
use App\Models\CarModel;
use App\Models\User;
use App\Services\CarModelService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarModelController extends Controller
{
    public function __construct(private readonly CarModelService $carModelService) {}

    public function index(): JsonResponse
    {
        $models = CarModel::query()->get();

        return ApiResponse::success(CarModelResource::collection($models)->resolve());
    }

    public function store(StoreCarModelRequest $request): JsonResponse
    {
        if (! $this->canCreateModel($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $carModel = $this->carModelService->store($request->validated());

        return ApiResponse::success(CarModelResource::make($carModel)->resolve());
    }

    public function show(CarModel $carModel): JsonResponse
    {
        return ApiResponse::success(CarModelResource::make($carModel)->resolve());
    }

    public function update(UpdateCarModelRequest $request, CarModel $carModel): JsonResponse
    {
        if (! $this->canManageModel($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedCarModel = $this->carModelService->update($carModel, $request->validated());

        return ApiResponse::success(CarModelResource::make($updatedCarModel)->resolve());
    }

    public function destroy(Request $request, CarModel $carModel): JsonResponse
    {
        if (! $this->canManageModel($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $this->carModelService->delete($carModel);

        return ApiResponse::success(['id' => $carModel->id]);
    }

    private function canCreateModel(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return in_array((int) $user->permetions_level, [1, 2, 3], true);
    }

    private function canManageModel(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return in_array((int) $user->permetions_level, [1, 2], true);
    }
}
