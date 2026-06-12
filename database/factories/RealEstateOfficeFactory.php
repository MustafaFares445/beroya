<?php

namespace Database\Factories;

use App\Models\Province;
use App\Models\RealEstateOffice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RealEstateOffice>
 */
class RealEstateOfficeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'province_id' => Province::factory(),
            'name' => fake()->unique()->company().' Office',
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
