<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_name' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
            'gallery_id' => 1,
            'permetions_level' => 4,
            'salary' => fake()->numberBetween(0, 1000),
            'phone' => fake()->numerify('09########'),
            'last_login' => null,
            'is_active' => true,
        ];
    }
}
