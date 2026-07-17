<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\Province;
use App\Models\RealEstateOffice;
use App\Models\User;
use App\Support\RealEstate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RealEstatePhaseTwoUserAssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_real_estate_assignment_fields(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Damascus Gallery',
            'address' => 'Main Street',
        ]);

        $province = Province::query()->create([
            'name' => 'Damascus',
            'is_active' => true,
        ]);

        $office = RealEstateOffice::query()->create([
            'province_id' => $province->id,
            'name' => 'Downtown Office',
            'address' => 'Central Avenue',
            'is_active' => true,
        ]);

        User::query()->create([
            'user_name' => 'agent-one',
            'password' => Hash::make('secret'),
            'gallery_id' => $gallery->id,
            'real_estate_office_id' => $office->id,
            'real_estate_role' => 'agent',
            'permetions_level' => 4,
            'salary' => 1200,
            'phone' => '0999000001',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'agent-one',
            'password' => 'secret',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user_name', 'agent-one')
            ->assertJsonPath('data.gallery_id', $gallery->id)
            ->assertJsonPath('data.real_estate_office_id', $office->id)
            ->assertJsonPath('data.real_estate_office_name', 'Downtown Office')
            ->assertJsonPath('data.real_estate_province_id', $province->id)
            ->assertJsonPath('data.real_estate_province_name', 'Damascus')
            ->assertJsonPath('data.real_estate_role', 'agent')
            ->assertJsonPath('data.real_estate_role_label', RealEstate::roleLabel('agent', 4))
            ->assertJsonPath('data.permetions_level', 4)
            ->assertJsonPath('data.salary', 1200)
            ->assertJsonPath('data.phone', '0999000001');
    }

    public function test_login_returns_direct_province_assignment_for_province_manager(): void
    {
        $province = Province::query()->create([
            'name' => 'Damascus',
            'is_active' => true,
        ]);

        User::query()->create([
            'user_name' => 'province-manager',
            'password' => Hash::make('secret'),
            'gallery_id' => 0,
            'real_estate_province_id' => $province->id,
            'real_estate_role' => 'province_manager',
            'permetions_level' => 2,
            'salary' => 2000,
            'phone' => '0999000002',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'province-manager',
            'password' => 'secret',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.real_estate_province_id', $province->id)
            ->assertJsonPath('data.real_estate_province_name', 'Damascus')
            ->assertJsonPath('data.real_estate_office_id', null)
            ->assertJsonPath('data.real_estate_office_name', null);
    }

    public function test_admin_can_store_and_update_user_real_estate_assignment_fields(): void
    {
        $province = Province::query()->create([
            'name' => 'Damascus',
            'is_active' => true,
        ]);

        $secondProvince = Province::query()->create([
            'name' => 'Aleppo',
            'is_active' => true,
        ]);

        $office = RealEstateOffice::query()->create([
            'province_id' => $province->id,
            'name' => 'Downtown Office',
            'address' => 'Central Avenue',
            'is_active' => true,
        ]);

        $secondOffice = RealEstateOffice::query()->create([
            'province_id' => $secondProvince->id,
            'name' => 'North Office',
            'address' => 'North Road',
            'is_active' => true,
        ]);

        $admin = User::query()->create([
            'user_name' => 'admin',
            'password' => Hash::make('secret'),
            'gallery_id' => 0,
            'permetions_level' => 1,
            'salary' => 0,
            'phone' => '0999000000',
        ]);

        $this->actingAsSanctum($admin);

        $createResponse = $this->postJson('/api/users', [
            'user_name' => 'real-estate-agent',
            'password' => 'secret',
            'gallery_id' => 0,
            'real_estate_office_id' => $office->id,
            'real_estate_role' => 'agent',
            'permetions_level' => 4,
            'salary' => 1200,
            'phone' => '0999000002',
        ]);

        $createResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user_name', 'real-estate-agent')
            ->assertJsonPath('data.real_estate_office_id', $office->id)
            ->assertJsonPath('data.real_estate_office_name', 'Downtown Office')
            ->assertJsonPath('data.real_estate_province_id', $province->id)
            ->assertJsonPath('data.real_estate_province_name', 'Damascus')
            ->assertJsonPath('data.real_estate_role', 'agent')
            ->assertJsonPath('data.real_estate_role_label', RealEstate::roleLabel('agent', 4))
            ->assertJsonPath('data.permetions_level', 4)
            ->assertJsonPath('data.salary', 1200)
            ->assertJsonPath('data.phone', '0999000002');

        $userId = (int) $createResponse->json('data.id');

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $userId,
                'user_name' => 'real-estate-agent',
                'real_estate_office_name' => 'Downtown Office',
                'real_estate_province_name' => 'Damascus',
                'real_estate_role' => 'agent',
                'real_estate_role_label' => RealEstate::roleLabel('agent', 4),
            ]);

        $updateResponse = $this->putJson("/api/users/{$userId}", [
            'user_name' => 'real-estate-agent-updated',
            'password' => null,
            'gallery_id' => 0,
            'real_estate_office_id' => $secondOffice->id,
            'real_estate_role' => 'senior-agent',
            'permetions_level' => 4,
            'salary' => 1500,
            'phone' => '0999000003',
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.user_name', 'real-estate-agent-updated')
            ->assertJsonPath('data.real_estate_office_id', $secondOffice->id)
            ->assertJsonPath('data.real_estate_office_name', 'North Office')
            ->assertJsonPath('data.real_estate_province_id', $secondProvince->id)
            ->assertJsonPath('data.real_estate_province_name', 'Aleppo')
            ->assertJsonPath('data.real_estate_role', 'senior-agent')
            ->assertJsonPath('data.real_estate_role_label', RealEstate::roleLabel('senior-agent', 4))
            ->assertJsonPath('data.salary', 1500)
            ->assertJsonPath('data.phone', '0999000003');

        $this->getJson("/api/users/{$userId}")
            ->assertOk()
            ->assertJsonPath('data.user_name', 'real-estate-agent-updated')
            ->assertJsonPath('data.real_estate_office_name', 'North Office')
            ->assertJsonPath('data.real_estate_province_name', 'Aleppo')
            ->assertJsonPath('data.real_estate_role', 'senior-agent')
            ->assertJsonPath('data.real_estate_role_label', RealEstate::roleLabel('senior-agent', 4));

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'real_estate_office_id' => $secondOffice->id,
            'real_estate_role' => 'senior-agent',
        ]);
    }
}
