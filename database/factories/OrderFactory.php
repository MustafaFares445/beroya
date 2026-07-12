<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_name' => fake()->name(),
            'client_phone' => '0999999999',
            'car_market' => 'BMW',
            'car_model' => 'X5',
            'year' => '2023',
            'price_low' => 10000,
            'price_high' => 20000,
            'order_state' => 'open',
            'order_notes' => fake()->sentence(),
            'user_name' => fake()->userName(),
            'gallery_name' => 'Aleppo',
            'checked' => 0,
            'approved_at' => null,
            'rejected_at' => null,
            'reviewed_by_user_id' => null,
            'reject_reason' => null,
        ];
    }
}
