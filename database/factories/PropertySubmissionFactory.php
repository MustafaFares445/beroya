<?php

namespace Database\Factories;

use App\Models\PropertyCategory;
use App\Models\PropertySubcategory;
use App\Models\PropertySubmission;
use App\Models\Province;
use App\Support\RealEstate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertySubmission>
 */
class PropertySubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'offer_number' => fake()->unique()->numerify('SUB-########'),
            'province_id' => Province::factory(),
            'office_id' => null,
            'main_category_id' => PropertyCategory::factory(),
            'subcategory_id' => PropertySubcategory::factory(),
            'property_nature' => fake()->randomElement(RealEstate::propertyNatureValues()),
            'title_type' => fake()->randomElement(RealEstate::titleTypeValues()),
            'area' => fake()->city(),
            'district' => fake()->streetName(),
            'address' => fake()->address(),
            'building' => fake()->secondaryAddress(),
            'floor' => (string) fake()->numberBetween(0, 20),
            'direction' => fake()->randomElement(['North', 'South', 'East', 'West']),
            'rooms_count' => fake()->numberBetween(1, 8),
            'area_size' => fake()->numberBetween(50, 400),
            'price' => fake()->numberBetween(10000, 100000),
            'ownership_type' => fake()->randomElement(['Owner', 'Agency']),
            'offer_type' => $offerType = fake()->randomElement(RealEstate::offerTypeValues()),
            'rent_duration' => $offerType === 'rent'
                ? fake()->randomElement(RealEstate::rentDurationValues())
                : null,
            'owner_name' => fake()->name(),
            'owner_phone' => fake()->numerify('09########'),
            'submission_note' => null,
            'status' => 'pending',
            'reject_reason' => null,
            'published_property_id' => null,
            'reviewed_at' => null,
        ];
    }
}
