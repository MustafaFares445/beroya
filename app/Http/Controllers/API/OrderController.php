<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        if (! $this->canReadOrders($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $orders = Order::query()->get();

        return ApiResponse::success(OrderResource::collection($orders)->resolve());
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        if (! $this->canManageOrders($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $order = $this->orderService->store($request->validated());

        return ApiResponse::success(OrderResource::make($order)->resolve());
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if (! $this->canReadOrders($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        return ApiResponse::success(OrderResource::make($order)->resolve());
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        if (! $this->canManageOrders($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedOrder = $this->orderService->update($order, $request->validated());

        return ApiResponse::success(OrderResource::make($updatedOrder)->resolve());
    }

    public function destroy(Request $request, Order $order): JsonResponse
    {
        if (! $this->canManageOrders($request)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $this->orderService->delete($order);

        return ApiResponse::success(['id' => $order->id]);
    }

    private function canReadOrders(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return in_array((int) $user->permetions_level, [1, 2, 3, 4], true);
    }

    private function canManageOrders(Request $request): bool
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return in_array((int) $user->permetions_level, [1, 2, 3], true);
    }
}
