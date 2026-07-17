<?php

namespace Tests\Feature;

use App\Models\Province;
use App\Models\RealEstateOffice;
use App\Models\User;
use App\Support\RealEstate;
use Database\Seeders\RealEstateLookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealEstatePhaseOneApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_read_seeded_real_estate_lookups(): void
    {
        $this->seed(RealEstateLookupSeeder::class);

        $provinceResponse = $this->getJson('/api/provinces')
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame(
            RealEstate::provinceNames(),
            collect($provinceResponse->json('data'))->pluck('name')->all()
        );

        $categoryResponse = $this->getJson('/api/real-estate/property-categories')
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame(
            array_keys(RealEstate::taxonomy()),
            collect($categoryResponse->json('data'))->pluck('name')->all()
        );

        $subcategoryResponse = $this->getJson('/api/real-estate/property-subcategories')
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame(
            array_merge(...array_values(RealEstate::taxonomy())),
            collect($subcategoryResponse->json('data'))->pluck('name')->all()
        );

        $optionsResponse = $this->getJson('/api/real-estate/options')
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame(RealEstate::optionGroups(), $optionsResponse->json('data'));
    }

    public function test_province_routes_require_auth_and_forbid_regular_users(): void
    {
        $this->postJson('/api/provinces', [
            'name' => 'Rif Dimashq',
            'is_active' => true,
        ])->assertUnauthorized();

        $regularUser = User::factory()->create([
            'permetions_level' => 4,
        ]);

        $this->actingAsSanctum($regularUser);

        $this->postJson('/api/provinces', [
            'name' => 'Rif Dimashq',
            'is_active' => true,
        ])
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');
    }

    public function test_admin_can_crud_provinces(): void
    {
        $admin = User::factory()->create([
            'permetions_level' => 1,
        ]);

        $this->actingAsSanctum($admin);

        $createResponse = $this->postJson('/api/provinces', [
            'name' => 'Rif Dimashq',
            'is_active' => true,
        ]);

        $createResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Rif Dimashq')
            ->assertJsonPath('data.is_active', true);

        $provinceId = (int) $createResponse->json('data.id');

        $updateResponse = $this->putJson("/api/provinces/{$provinceId}", [
            'name' => 'Damascus Countryside',
            'is_active' => false,
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.name', 'Damascus Countryside')
            ->assertJsonPath('data.is_active', false);

        $this->getJson("/api/provinces/{$provinceId}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Damascus Countryside');

        $this->getJson('/api/provinces')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->deleteJson("/api/provinces/{$provinceId}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $provinceId);

        $this->assertDatabaseMissing('provinces', [
            'id' => $provinceId,
        ]);
    }

    public function test_manager_can_crud_real_estate_offices(): void
    {
        $province = Province::query()->create([
            'name' => 'Damascus',
            'is_active' => true,
        ]);

        $manager = User::factory()->create([
            'permetions_level' => 2,
            'real_estate_province_id' => $province->id,
            'real_estate_role' => 'province_manager',
        ]);

        $this->actingAsSanctum($manager);

        $createResponse = $this->postJson('/api/real-estate/offices', [
            'province_id' => $province->id,
            'name' => 'Downtown Office',
            'address' => 'Main Street',
            'is_active' => true,
        ]);

        $createResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.province_name', 'Damascus')
            ->assertJsonPath('data.name', 'Downtown Office');

        $officeId = (int) $createResponse->json('data.id');

        $this->getJson('/api/real-estate/offices')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Downtown Office']);

        $updateResponse = $this->putJson("/api/real-estate/offices/{$officeId}", [
            'province_id' => $province->id,
            'name' => 'Updated Downtown Office',
            'address' => 'Updated Main Street',
            'is_active' => false,
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.province_name', 'Damascus')
            ->assertJsonPath('data.is_active', false);

        $this->getJson("/api/real-estate/offices/{$officeId}")
            ->assertOk()
            ->assertJsonPath('data.province_name', 'Damascus')
            ->assertJsonPath('data.name', 'Updated Downtown Office');

        $this->deleteJson("/api/real-estate/offices/{$officeId}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $officeId);

        $this->assertDatabaseMissing('real_estate_offices', [
            'id' => $officeId,
        ]);
    }

    public function test_manager_can_crud_real_estate_office_phones(): void
    {
        $province = Province::query()->create([
            'name' => 'Homs',
            'is_active' => true,
        ]);

        $office = RealEstateOffice::query()->create([
            'province_id' => $province->id,
            'name' => 'Homs Office',
            'address' => 'Center',
            'is_active' => true,
        ]);

        $manager = User::factory()->create([
            'permetions_level' => 2,
            'real_estate_province_id' => $province->id,
            'real_estate_role' => 'province_manager',
        ]);

        $this->actingAsSanctum($manager);

        $createResponse = $this->postJson('/api/real-estate/office-phones', [
            'real_estate_office_id' => $office->id,
            'phone' => '+963940000000',
        ]);

        $createResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.real_estate_office_id', $office->id)
            ->assertJsonPath('data.phone', '+963940000000');

        $phoneId = (int) $createResponse->json('data.id');

        $this->getJson('/api/real-estate/office-phones')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['phone' => '+963940000000']);

        $updateResponse = $this->putJson("/api/real-estate/office-phones/{$phoneId}", [
            'real_estate_office_id' => $office->id,
            'phone' => '+963944444444',
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.phone', '+963944444444');

        $this->getJson("/api/real-estate/office-phones/{$phoneId}")
            ->assertOk()
            ->assertJsonPath('data.phone', '+963944444444');

        $this->deleteJson("/api/real-estate/office-phones/{$phoneId}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $phoneId);

        $this->assertDatabaseMissing('real_estate_office_phones', [
            'id' => $phoneId,
        ]);
    }
}
