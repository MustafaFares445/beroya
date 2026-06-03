<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCarSaleStateRequest;
use App\Models\Car;
use App\Models\User;
use App\Services\CarService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarSaleStateController extends Controller
{
    public function __construct(private readonly CarService $carService)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateCarSaleStateRequest $request, Car $car): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null || !in_array((int) $user->permetions_level, [1, 2, 3, 4], true)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $this->carService->updateSaleState($car, (int) $request->validated('sale_state'));

        return ApiResponse::success();
    }
}

