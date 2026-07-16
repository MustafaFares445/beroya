<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\PropertySubcategory;
use App\Models\PropertySubmission;
use App\Models\Province;
use App\Models\RealEstateOffice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealEstatePhaseFiveHardeningApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_estate_routes_return_validation_errors_for_bad_payloads(): void
    {
        $admin = User::factory()->create([
            'permetions_level' => 1,
        ]);

        $this->actingAsSanctum($admin);

        $this->postJson('/api/provinces', [
            'name' => 'Rif Dimashq',
        ])
            ->assertStatus(422)
            ->assertJsonPath('status', 'failure')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'errors' => [
                        'is_active',
                    ],
                ],
            ]);

        $province = Province::query()->create([
            'name' => 'Damascus',
            'is_active' => true,
        ]);

        $this->postJson('/api/real-estate/offices', [
            'province_id' => 999999,
            'name' => 'Invalid Office',
            'address' => 'Main Street',
            'is_active' => true,
        ])
            ->assertStatus(422)
            ->assertJsonPath('status', 'failure')
            ->assertJsonStructure([
                'data' => [
                    'errors' => [
                        'province_id',
                    ],
                ],
            ]);

        $context = $this->createLookupContext('Damascus', 'سكني', 'منزل', 'Downtown Office');

        $property = Property::query()->create([
            ...$this->propertyPayload(
                $context['office']->id,
                $context['category']->id,
                $context['subcategory']->id,
                'OFF-9001'
            ),
            'province_id' => $province->id,
        ]);

        $this->postJson('/api/real-estate/property-submissions', [
            ...$this->submissionPayload(
                $context['office']->id,
                $province->id,
                $context['category']->id,
                $context['subcategory']->id,
                'OFF-9001'
            ),
        ])
            ->assertStatus(422)
            ->assertJsonPath('status', 'failure')
            ->assertJsonStructure([
                'data' => [
                    'errors' => [
                        'offer_number',
                    ],
                ],
            ]);

        $this->postJson("/api/real-estate/properties/{$property->id}/images", [])
            ->assertStatus(422)
            ->assertJsonPath('status', 'failure')
            ->assertJsonStructure([
                'data' => [
                    'errors' => [
                        'images',
                    ],
                ],
            ]);
    }

    public function test_regular_users_are_forbidden_from_mutating_real_estate_routes(): void
    {
        $context = $this->createLookupContext('Homs', 'تجاري', 'محلات', 'Homs Office');

        $regularUser = User::factory()->create([
            'permetions_level' => 4,
        ]);

        $this->actingAsSanctum($regularUser);

        $this->postJson('/api/real-estate/properties', $this->propertyPayload(
            $context['office']->id,
            $context['category']->id,
            $context['subcategory']->id,
            'OFF-9101'
        ))
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');

        $submission = PropertySubmission::query()->create([
            ...$this->submissionPayload(
                $context['office']->id,
                $context['province']->id,
                $context['category']->id,
                $context['subcategory']->id,
                'SUB-9101'
            ),
        ]);

        $this->putJson("/api/real-estate/property-submissions/{$submission->id}/approve")
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');
    }

    public function test_property_validation_enforces_investment_title_type_and_rent_duration_rules(): void
    {
        $admin = User::factory()->create([
            'permetions_level' => 1,
        ]);

        $this->actingAsSanctum($admin);

        $context = $this->createLookupContext('Damascus', 'سكني', 'منزل', 'Downtown Office');

        $investmentResponse = $this->postJson('/api/real-estate/properties', $this->propertyPayload(
            $context['office']->id,
            $context['category']->id,
            $context['subcategory']->id,
            'OFF-9201',
            'investment'
        ));

        $investmentResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.offer_type', 'investment')
            ->assertJsonPath('data.rent_duration', null);

        $missingTitleType = $this->propertyPayload(
            $context['office']->id,
            $context['category']->id,
            $context['subcategory']->id,
            'OFF-9202'
        );
        unset($missingTitleType['title_type']);

        $this->postJson('/api/real-estate/properties', $missingTitleType)
            ->assertStatus(422)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data.errors.title_type.0', 'The title type field is required.');

        $rentWithoutDuration = $this->propertyPayload(
            $context['office']->id,
            $context['category']->id,
            $context['subcategory']->id,
            'OFF-9203',
            'rent'
        );

        $this->postJson('/api/real-estate/properties', $rentWithoutDuration)
            ->assertStatus(422)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data.errors.rent_duration.0', 'The rent duration field is required when offer type is rent.');
    }

    public function test_office_employees_cannot_approve_or_reject_property_submissions(): void
    {
        $context = $this->createLookupContext('Homs', 'تجاري', 'محلات', 'Homs Office');

        $employee = User::query()->create([
            'user_name' => 'office-employee',
            'password' => bcrypt('secret'),
            'gallery_id' => 0,
            'real_estate_office_id' => $context['office']->id,
            'real_estate_role' => 'office_employee',
            'permetions_level' => 4,
            'salary' => 0,
            'phone' => '0999000020',
        ]);

        $submission = PropertySubmission::query()->create([
            ...$this->submissionPayload(
                $context['office']->id,
                $context['province']->id,
                $context['category']->id,
                $context['subcategory']->id,
                'SUB-9301'
            ),
        ]);

        $this->actingAsSanctum($employee);

        $this->putJson("/api/real-estate/property-submissions/{$submission->id}/approve")
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');

        $this->putJson("/api/real-estate/property-submissions/{$submission->id}/reject", [
            'reject_reason' => 'Not authorized',
        ])
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyPayload(
        int $officeId,
        int $categoryId,
        int $subcategoryId,
        string $offerNumber,
        string $offerType = 'sale',
        ?string $rentDuration = null
    ): array {
        return [
            'offer_number' => $offerNumber,
            'office_id' => $officeId,
            'main_category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'property_nature' => 'سكني',
            'title_type' => 'ملك',
            'area' => 'City Center',
            'district' => 'Downtown',
            'address' => 'Main Street 10',
            'building' => 'Building A',
            'floor' => '3',
            'direction' => 'East',
            'rooms_count' => 3,
            'area_size' => 120,
            'price' => 65000,
            'ownership_type' => 'Owner',
            'offer_type' => $offerType,
            'rent_duration' => $rentDuration,
            'owner_name' => 'Owner One',
            'owner_phone' => '0999000001',
            'status' => 'available',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function submissionPayload(
        ?int $officeId,
        int $provinceId,
        int $categoryId,
        int $subcategoryId,
        string $offerNumber,
        string $offerType = 'sale',
        ?string $rentDuration = null,
        ?string $submissionNote = null
    ): array {
        return array_filter([
            'offer_number' => $offerNumber,
            'office_id' => $officeId,
            'province_id' => $provinceId,
            'main_category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'property_nature' => 'سكني',
            'title_type' => 'ملك',
            'area' => 'City Center',
            'district' => 'Downtown',
            'address' => 'Main Street 10',
            'building' => 'Building A',
            'floor' => '3',
            'direction' => 'East',
            'rooms_count' => 3,
            'area_size' => 120,
            'price' => 65000,
            'ownership_type' => 'Owner',
            'offer_type' => $offerType,
            'rent_duration' => $rentDuration,
            'owner_name' => 'Owner One',
            'owner_phone' => '0999000001',
            'submission_note' => $submissionNote,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array{
     *     province: Province,
     *     office: RealEstateOffice,
     *     category: PropertyCategory,
     *     subcategory: PropertySubcategory
     * }
     */
    private function createLookupContext(
        string $provinceName,
        string $categoryName,
        string $subcategoryName,
        string $officeName
    ): array {
        $province = Province::query()->create([
            'name' => $provinceName,
            'is_active' => true,
        ]);

        $office = RealEstateOffice::query()->create([
            'province_id' => $province->id,
            'name' => $officeName,
            'address' => $officeName.' Address',
            'is_active' => true,
        ]);

        $category = PropertyCategory::query()->create([
            'name' => $categoryName,
        ]);

        $subcategory = $category->subcategories()->create([
            'name' => $subcategoryName,
        ]);

        return [
            'province' => $province,
            'office' => $office,
            'category' => $category,
            'subcategory' => $subcategory,
        ];
    }
}
