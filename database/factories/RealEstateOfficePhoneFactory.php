<?php

namespace Database\Factories;

use App\Models\RealEstateOffice;
use App\Models\RealEstateOfficePhone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RealEstateOfficePhone>
 */
class RealEstateOfficePhoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'real_estate_office_id' => RealEstateOffice::factory(),
            'phone' => fake()->numerify('09########'),
        ];
    }
}
