<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use App\Models\Week;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_name' => fake()->name(),
            'user_position' => '4',
            'user_gallery' => 'Aleppo',
            'sales_count' => 0,
            'sales_amount' => 0,
            'deduction_amount' => 0,
            'working_days_count' => 0,
            'salary' => fake()->numberBetween(0, 1000),
            'week_id' => Week::factory(),
            'year' => (string) now()->format('Y'),
            'total_amount' => 0,
            'received' => '0',
        ];
    }
}
