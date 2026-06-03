<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\User;
use App\Models\Week;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_comiss' => fake()->numberBetween(0, 500),
            'user_note' => fake()->sentence(),
            'buyer_name' => fake()->firstName(),
            'buyer_phone' => fake()->numberBetween(900000000, 999999999),
            'owner_comiss' => 0,
            'owner_comiss_payed' => 0,
            'buyer_comiss' => 0,
            'buyer_comiss_payed' => 0,
            'owner_id_image' => '',
            'buyer_id_image' => '',
            'contract_image' => '',
            'date' => now()->toDateString(),
            'week_id' => Week::factory(),
            'car_brand' => 'BMW',
            'car_model' => 'X5',
            'car_name' => 'BMW X5',
            'user_id' => User::factory(),
            'car_id' => 1,
            'car_number' => '123456',
            'price' => fake()->numberBetween(10000, 50000),
            'employee_name' => fake()->name(),
            'owner_name' => fake()->name(),
            'owner_phone' => '0999999999',
            'status' => 'hold',
            'approved' => '0',
        ];
    }

    public function done(): static
    {
        return $this->state(fn (): array => [
            'status' => 'done',
            'approved' => '1',
            'buyer_comiss' => 100,
        ]);
    }
}
