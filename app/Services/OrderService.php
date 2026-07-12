<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private readonly WorkflowNotificationService $workflowNotificationService) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): Order
    {
        return DB::transaction(function () use ($payload): Order {
            $order = Order::query()->create([
                'client_name' => (string) $payload['client_name'],
                'client_phone' => (string) $payload['client_phone'],
                'car_market' => (string) $payload['car_market'],
                'car_model' => (string) $payload['car_model'],
                'year' => (string) $payload['year'],
                'price_low' => (int) $payload['price_low'],
                'price_high' => (int) $payload['price_high'],
                'order_state' => (string) $payload['order_state'],
                'order_notes' => (string) $payload['order_notes'],
                'user_name' => (string) $payload['user_name'],
                'gallery_name' => (string) $payload['gallery_name'],
                'checked' => 0,
                'approved_at' => null,
                'rejected_at' => null,
                'reviewed_by_user_id' => null,
                'reject_reason' => null,
            ]);

            $this->workflowNotificationService->notifyManagers(
                'order',
                'created',
                'New order submitted',
                sprintf('Order #%d is waiting for review.', $order->id),
                'Order',
                $order->id,
                [
                    'order_state' => (string) $order->order_state,
                    'gallery_name' => (string) $order->gallery_name,
                    'client_name' => (string) $order->client_name,
                ],
            );

            return $order->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Order $order, array $payload): Order
    {
        return DB::transaction(static function () use ($order, $payload): Order {
            $order->update([
                'client_name' => (string) $payload['client_name'],
                'client_phone' => (string) $payload['client_phone'],
                'car_market' => (string) $payload['car_market'],
                'car_model' => (string) $payload['car_model'],
                'year' => (string) $payload['year'],
                'price_low' => (int) $payload['price_low'],
                'price_high' => (int) $payload['price_high'],
                'order_state' => (string) $payload['order_state'],
                'order_notes' => (string) $payload['order_notes'],
                'user_name' => (string) $payload['user_name'],
                'gallery_name' => (string) $payload['gallery_name'],
            ]);

            return $order->fresh();
        });
    }

    public function delete(Order $order): void
    {
        $order->delete();
    }

    public function updateChecked(Order $order, bool $checked): Order
    {
        return DB::transaction(function () use ($order, $checked): Order {
            $order->update([
                'checked' => (int) $checked,
            ]);

            return $order->fresh();
        });
    }

    public function approve(Order $order, User $actor, ?string $ipAddress = null): Order
    {
        return DB::transaction(function () use ($order, $actor, $ipAddress): Order {
            $order->update([
                'order_state' => 'approved',
                'approved_at' => now(),
                'rejected_at' => null,
                'reviewed_by_user_id' => $actor->id,
                'reject_reason' => null,
            ]);

            $updatedOrder = $order->fresh();

            $this->workflowNotificationService->notifyManagers(
                'order',
                'approved',
                'Order approved',
                sprintf('Order #%d was approved.', $updatedOrder->id),
                'Order',
                $updatedOrder->id,
                [
                    'order_state' => (string) $updatedOrder->order_state,
                    'reviewed_by_user_id' => $actor->id,
                    'ip_address' => $ipAddress,
                ],
            );

            return $updatedOrder;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function reject(Order $order, array $payload, User $actor, ?string $ipAddress = null): Order
    {
        return DB::transaction(function () use ($order, $payload, $actor, $ipAddress): Order {
            $order->update([
                'order_state' => 'rejected',
                'approved_at' => null,
                'rejected_at' => now(),
                'reviewed_by_user_id' => $actor->id,
                'reject_reason' => (string) $payload['reject_reason'],
            ]);

            $updatedOrder = $order->fresh();

            $this->workflowNotificationService->notifyManagers(
                'order',
                'rejected',
                'Order rejected',
                sprintf('Order #%d was rejected.', $updatedOrder->id),
                'Order',
                $updatedOrder->id,
                [
                    'order_state' => (string) $updatedOrder->order_state,
                    'reject_reason' => (string) $updatedOrder->reject_reason,
                    'reviewed_by_user_id' => $actor->id,
                    'ip_address' => $ipAddress,
                ],
            );

            return $updatedOrder;
        });
    }
}
