<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Deduction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deduction>
 */
class DeductionFactory extends Factory
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
