<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_user_id' => User::factory(),
            'gallery_id' => null,
            'action_type' => 'car.created',
            'target_type' => 'Car',
            'target_id' => fake()->numberBetween(1, 1000),
            'old_values' => [],
            'new_values' => [
                'status' => 'available',
            ],
            'ip_address' => fake()->ipv4(),
            'created_at' => now(),
        ];
    }
}
