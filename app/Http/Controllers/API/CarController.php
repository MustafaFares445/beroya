<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexCarRequest;
use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UpdateCarRequest;
use App\Http\Resources\CarResource;
use App\Models\Car;
use App\Models\User;
use App\Services\CarService;
use App\Support\ApiResponse;
use App\Support\SanctumUserResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CarController extends Controller
{
    public function __construct(private readonly CarService $carService) {}

    public function index(IndexCarRequest $request): JsonResponse
    {
        $cars = $this->carsQuery($request->validated())->get();

        return ApiResponse::success($this->resolveCarsData($request, $cars));
    }

    public function show(Request $request, Car $car): JsonResponse
    {
        return ApiResponse::success($this->resolveCarData($request, $car));
    }

    public function latest(Request $request): JsonResponse
    {
        $cars = Car::query()
            ->where('car_sale_state', '!=', 4)
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        return ApiResponse::success($this->resolveCarsData($request, $cars));
    }

    public function store(StoreCarRequest $request): JsonResponse
    {
        if (! $this->canManageCars($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $car = $this->carService->store(
            $request->validated(),
            $request->user(),
            $request->ip(),
        );

        return ApiResponse::success(CarResource::make($car)->resolve());
    }

    public function update(UpdateCarRequest $request, Car $car): JsonResponse
    {
        if (! $this->canManageCars($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedCar = $this->carService->update(
            $car,
            $request->validated(),
            $request->user(),
            $request->ip(),
        );

        return ApiResponse::success(CarResource::make($updatedCar)->resolve());
    }

    public function destroy(Request $request, Car $car): JsonResponse
    {
        if (! $this->canManageCars($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $carId = $car->id;
        $this->carService->delete($car, $request->user(), $request->ip());

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
     * @param  array<string, mixed>  $filters
     */
    private function carsQuery(array $filters): Builder
    {
        $query = Car::query();

        if (! array_key_exists('market_id', $filters) || $filters['market_id'] === null || $filters['market_id'] === '') {
            return $query;
        }

        if (isset($filters['model_id'])) {
            $query->where('model_id', (int) $filters['model_id']);
        }

        return $query->where('market_id', (int) $filters['market_id']);
    }

    /**
     * @param  Collection<int, Car>  $cars
     * @return array<int, array<string, mixed>>
     */
    private function resolveCarsData(Request $request, Collection $cars): array
    {
        $viewer = $this->resolveViewerUser($request);
        if ($viewer !== null && in_array((int) $viewer->permetions_level, [1, 2, 3], true)) {
            return CarResource::collection($cars)->resolve();
        }

        return $cars
            ->map(function (Car $car): array {
                return $this->sanitizeCarData(CarResource::make($car)->resolve());
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveCarData(Request $request, Car $car): array
    {
        $carData = CarResource::make($car)->resolve();
        $viewer = $this->resolveViewerUser($request);
        if ($viewer !== null && in_array((int) $viewer->permetions_level, [1, 2, 3], true)) {
            return $carData;
        }

        return $this->sanitizeCarData($carData);
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
