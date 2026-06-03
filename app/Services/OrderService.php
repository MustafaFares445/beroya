<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): Order
    {
        return DB::transaction(static function () use ($payload): Order {
            return Order::query()->create([
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
}
