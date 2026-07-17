<?php

namespace Tests\Feature;

use App\Models\PropertyCategory;
use App\Models\PropertySubmission;
use App\Models\Province;
use App\Models\RealEstateOffice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealEstateRoleAuthorizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_province_manager_can_manage_only_offices_in_assigned_province(): void
    {
        [$province, $office] = $this->createProvinceWithOffice('Damascus');
        [$otherProvince, $otherOffice] = $this->createProvinceWithOffice('Aleppo');
        $manager = User::factory()->create([
            'permetions_level' => 2,
            'real_estate_province_id' => $province->id,
            'real_estate_role' => 'province_manager',
        ]);

        $this->actingAsSanctum($manager);

        $this->postJson('/api/real-estate/offices', $this->officePayload($province->id, 'Second Damascus Office'))
            ->assertOk();

        $this->putJson(
            "/api/real-estate/offices/{$office->id}",
            $this->officePayload($province->id, 'Updated Damascus Office')
        )->assertOk();

        $this->putJson(
            "/api/real-estate/offices/{$otherOffice->id}",
            $this->officePayload($otherProvince->id, 'Forbidden Update')
        )->assertForbidden();

        $this->postJson('/api/real-estate/offices', $this->officePayload($otherProvince->id, 'Forbidden Office'))
            ->assertForbidden();
    }

    public function test_only_general_manager_can_mutate_provinces(): void
    {
        $province = Province::factory()->create();
        $provinceManager = User::factory()->create([
            'permetions_level' => 2,
            'real_estate_province_id' => $province->id,
            'real_estate_role' => 'province_manager',
        ]);

        $this->actingAsSanctum($provinceManager);

        $this->postJson('/api/provinces', [
            'name' => 'Homs',
            'is_active' => true,
        ])->assertForbidden();

        $this->putJson("/api/provinces/{$province->id}", [
            'name' => 'Updated Province',
            'is_active' => true,
        ])->assertForbidden();

        $this->deleteJson("/api/provinces/{$province->id}")->assertForbidden();
    }

    public function test_office_manager_can_update_only_assigned_office(): void
    {
        [$province, $office] = $this->createProvinceWithOffice('Damascus');
        [, $otherOffice] = $this->createProvinceWithOffice('Aleppo');
        $manager = User::factory()->create([
            'permetions_level' => 3,
            'real_estate_office_id' => $office->id,
            'real_estate_role' => 'office_manager',
        ]);

        $this->actingAsSanctum($manager);

        $this->putJson(
            "/api/real-estate/offices/{$office->id}",
            $this->officePayload($province->id, 'Updated Own Office')
        )->assertOk();

        $this->putJson(
            "/api/real-estate/offices/{$otherOffice->id}",
            $this->officePayload((int) $otherOffice->province_id, 'Forbidden Other Office')
        )->assertForbidden();

        $this->postJson('/api/real-estate/offices', $this->officePayload($province->id, 'Forbidden New Office'))
            ->assertForbidden();

        $this->deleteJson("/api/real-estate/offices/{$office->id}")->assertForbidden();
    }

    public function test_province_manager_lists_and_creates_only_lower_users_in_assigned_province(): void
    {
        [$province, $office] = $this->createProvinceWithOffice('Damascus');
        [, $otherOffice] = $this->createProvinceWithOffice('Aleppo');
        $manager = User::factory()->create([
            'permetions_level' => 2,
            'real_estate_province_id' => $province->id,
            'real_estate_role' => 'province_manager',
        ]);
        $localEmployee = User::factory()->create([
            'permetions_level' => 4,
            'real_estate_office_id' => $office->id,
            'real_estate_role' => 'office_employee',
        ]);
        $outsideEmployee = User::factory()->create([
            'permetions_level' => 4,
            'real_estate_office_id' => $otherOffice->id,
            'real_estate_role' => 'office_employee',
        ]);

        $this->actingAsSanctum($manager);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $localEmployee->id])
            ->assertJsonMissing(['id' => $outsideEmployee->id]);

        $this->postJson('/api/users', $this->userPayload(3, $office->id, 'new-office-manager'))
            ->assertOk();

        $this->postJson('/api/users', $this->userPayload(3, $otherOffice->id, 'outside-manager'))
            ->assertForbidden();

        $this->postJson('/api/users', [
            ...$this->userPayload(2, null, 'peer-manager'),
            'real_estate_province_id' => $province->id,
            'real_estate_role' => 'province_manager',
        ])->assertForbidden();
    }

    public function test_office_manager_manages_only_level_four_users_in_assigned_office(): void
    {
        [, $office] = $this->createProvinceWithOffice('Damascus');
        [, $otherOffice] = $this->createProvinceWithOffice('Aleppo');
        $manager = User::factory()->create([
            'permetions_level' => 3,
            'real_estate_office_id' => $office->id,
            'real_estate_role' => 'office_manager',
        ]);

        $this->actingAsSanctum($manager);

        $created = $this->postJson('/api/users', $this->userPayload(4, $office->id, 'local-employee'))
            ->assertOk();

        $this->postJson('/api/users', $this->userPayload(4, $otherOffice->id, 'outside-employee'))
            ->assertForbidden();

        $this->postJson('/api/users', $this->userPayload(3, $office->id, 'peer-office-manager'))
            ->assertForbidden();

        $employeeId = (int) $created->json('data.id');

        $this->putJson(
            "/api/users/{$employeeId}",
            $this->userPayload(4, $office->id, 'updated-local-employee', false)
        )->assertOk();

        $this->deleteJson("/api/users/{$employeeId}")->assertOk();
    }

    public function test_office_manager_can_approve_property_submission(): void
    {
        [$province, $office] = $this->createProvinceWithOffice('Damascus');
        $category = PropertyCategory::factory()->create();
        $subcategory = $category->subcategories()->create(['name' => 'Apartment']);
        $submission = PropertySubmission::factory()->create([
            'province_id' => $province->id,
            'office_id' => $office->id,
            'main_category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
        ]);
        $manager = User::factory()->create([
            'permetions_level' => 3,
            'real_estate_office_id' => $office->id,
            'real_estate_role' => 'office_manager',
        ]);
        $this->actingAsSanctum($manager);
        $this->putJson("/api/real-estate/property-submissions/{$submission->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }

    /**
     * @return array{Province, RealEstateOffice}
     */
    private function createProvinceWithOffice(string $provinceName): array
    {
        $province = Province::factory()->create(['name' => $provinceName]);
        $office = RealEstateOffice::factory()->create(['province_id' => $province->id]);

        return [$province, $office];
    }

    /**
     * @return array<string, mixed>
     */
    private function officePayload(int $provinceId, string $name): array
    {
        return [
            'province_id' => $provinceId,
            'name' => $name,
            'address' => 'Main Street',
            'is_active' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(int $level, ?int $officeId, string $userName, bool $includePassword = true): array
    {
        return array_filter([
            'user_name' => $userName,
            'password' => $includePassword ? 'secret-password' : null,
            'gallery_id' => 0,
            'real_estate_office_id' => $officeId,
            'real_estate_role' => $level === 3 ? 'office_manager' : 'office_employee',
            'permetions_level' => $level,
            'salary' => 100,
            'phone' => fake()->unique()->numerify('09########'),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
