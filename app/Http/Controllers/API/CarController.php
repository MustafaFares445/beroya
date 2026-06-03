<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UpdateCarRequest;
use App\Http\Resources\CarResource;
use App\Models\Car;
use App\Models\User;
use App\Services\CarService;
use App\Support\ApiResponse;
use App\Support\SanctumUserResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function __construct(private readonly CarService $carService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $cars = Car::query()->get();
        $viewer = $this->resolveViewerUser($request);
        if ($viewer !== null && in_array((int) $viewer->permetions_level, [1, 2, 3], true)) {
            return ApiResponse::success(CarResource::collection($cars)->resolve());
        }

        $sanitizedCars = $cars
            ->map(function (Car $car): array {
                return $this->sanitizeCarData(CarResource::make($car)->resolve());
            })
            ->all();

        return ApiResponse::success($sanitizedCars);
    }

    public function show(Request $request, Car $car): JsonResponse
    {
        $carData = CarResource::make($car)->resolve();
        $viewer = $this->resolveViewerUser($request);
        if ($viewer !== null && in_array((int) $viewer->permetions_level, [1, 2, 3], true)) {
            return ApiResponse::success($carData);
        }

        return ApiResponse::success($this->sanitizeCarData($carData));
    }

    public function store(StoreCarRequest $request): JsonResponse
    {
        if (!$this->canManageCars($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $car = $this->carService->store($request->validated());

        return ApiResponse::success(CarResource::make($car)->resolve());
    }

    public function update(UpdateCarRequest $request, Car $car): JsonResponse
    {
        if (!$this->canManageCars($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedCar = $this->carService->update($car, $request->validated());

        return ApiResponse::success(CarResource::make($updatedCar)->resolve());
    }

    public function destroy(Request $request, Car $car): JsonResponse
    {
        if (!$this->canManageCars($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $carId = $car->id;
        $this->carService->delete($car);

        return ApiResponse::success(null, 200, ['id' => $carId]);
    }

    private function canManageCars(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return in_array((int) $user->permetions_level, [1, 2, 3], true);
    }

    private function resolveViewerUser(Request $request): ?User
    {
        return SanctumUserResolver::fromRequest($request);
    }

    /**
     * @param  array<string, mixed>  $carData
     * @return array<string, mixed>
     */
    private function sanitizeCarData(array $carData): array
    {
        $carData['plateNumber'] = '';
        $carData['owner_name'] = '';
        $carData['owner_phone'] = '';

        return $carData;
    }
}

