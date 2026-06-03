<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Bonus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bonus>
 */
class BonusFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->numberBetween(10, 500),
            'description' => fake()->sentence(),
            'accountant_id' => Account::factory(),
        ];
    }
}
