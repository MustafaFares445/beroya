<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class RejectOrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function __invoke(RejectOrderRequest $request, Order $order): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null || ! $this->canReviewOrders($user)) {
            return ApiResponse::failureData('your computer harmly damaged', 403, 'responses.forbidden');
        }

        $updatedOrder = $this->orderService->reject(
            $order,
            $request->validated(),
            $user,
            $request->ip(),
        );

        return ApiResponse::success(OrderResource::make($updatedOrder)->resolve());
    }

    private function canReviewOrders(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2], true);
    }
}
