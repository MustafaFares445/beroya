<?php

namespace Database\Factories;

use App\Models\PropertyCategory;
use App\Models\PropertySubcategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertySubcategory>
 */
class PropertySubcategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_category_id' => PropertyCategory::factory(),
            'name' => fake()->unique()->word(),
        ];
    }
}
