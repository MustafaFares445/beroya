<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Aleppo',
            'address' => 'Main',
        ]);

        $admin = User::query()->create([
            'user_name' => 'admin',
            'password' => Hash::make('secret'),
            'gallery_id' => 0,
            'permetions_level' => 1,
            'salary' => 0,
            'phone' => '',
        ]);

        User::factory()->create([
            'gallery_id' => $gallery->id,
        ]);

        $this->actingAsSanctum($admin);

        $response = $this->getJson('/api/users');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data');

        $this->assertSame(1, $admin->permetions_level);
    }

    public function test_regular_user_cannot_list_users(): void
    {
        $user = User::factory()->create([
            'permetions_level' => 4,
        ]);

        $this->actingAsSanctum($user);

        $response = $this->getJson('/api/users');

        $response
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');

        $this->assertSame(4, $user->permetions_level);
    }

    public function test_manager_cannot_create_admin_user(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Damascus',
            'address' => 'Airport Road',
        ]);

        $manager = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 2,
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->postJson('/api/users', [
            'user_name' => 'new-admin',
            'password' => 'secret',
            'gallery_id' => $gallery->id,
            'permetions_level' => 1,
            'salary' => 100,
            'phone' => '0999999999',
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');
    }

    public function test_car_manager_can_create_gallery_user_without_real_estate_assignments(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Damascus',
            'address' => 'Airport Road',
        ]);

        $manager = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 2,
            'real_estate_province_id' => null,
            'real_estate_office_id' => null,
            'real_estate_role' => null,
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->postJson('/api/users', [
            'user_name' => 'car-sales-user',
            'password' => 'secret',
            'gallery_id' => $gallery->id,
            'real_estate_province_id' => null,
            'real_estate_office_id' => null,
            'real_estate_role' => null,
            'permetions_level' => 3,
            'salary' => 100,
            'phone' => '0999999999',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.real_estate_province_id', null)
            ->assertJsonPath('data.real_estate_province_name', null)
            ->assertJsonPath('data.real_estate_office_id', null)
            ->assertJsonPath('data.real_estate_office_name', null)
            ->assertJsonPath('data.real_estate_role', null)
            ->assertJsonPath('data.real_estate_role_label', null);

        $this->assertDatabaseHas('users', [
            'user_name' => 'car-sales-user',
            'gallery_id' => $gallery->id,
            'permetions_level' => 3,
            'real_estate_province_id' => null,
            'real_estate_office_id' => null,
            'real_estate_role' => null,
        ]);
    }

    public function test_car_manager_lists_only_users_from_own_gallery(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Damascus',
            'address' => 'Airport Road',
        ]);
        $otherGallery = Gallery::query()->create([
            'name' => 'Aleppo',
            'address' => 'Main Street',
        ]);

        $manager = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 2,
            'real_estate_province_id' => null,
            'real_estate_office_id' => null,
            'real_estate_role' => null,
        ]);
        $localUser = User::factory()->create(['gallery_id' => $gallery->id]);
        $outsideUser = User::factory()->create(['gallery_id' => $otherGallery->id]);

        $this->actingAsSanctum($manager);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $localUser->id])
            ->assertJsonMissing(['id' => $outsideUser->id]);
    }

    public function test_car_admin_can_update_user_without_real_estate_assignments(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Damascus',
            'address' => 'Airport Road',
        ]);

        $admin = User::factory()->create([
            'gallery_id' => 0,
            'permetions_level' => 1,
            'real_estate_province_id' => null,
            'real_estate_office_id' => null,
            'real_estate_role' => null,
        ]);
        $user = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 3,
            'real_estate_province_id' => null,
            'real_estate_office_id' => null,
            'real_estate_role' => null,
        ]);

        $this->actingAsSanctum($admin);

        $this->putJson("/api/users/{$user->id}", [
            'user_name' => 'updated-car-user',
            'password' => null,
            'gallery_id' => $gallery->id,
            'real_estate_province_id' => null,
            'real_estate_office_id' => null,
            'real_estate_role' => null,
            'permetions_level' => 3,
            'salary' => 200,
            'phone' => '0999999998',
        ])
            ->assertOk()
            ->assertJsonPath('data.user_name', 'updated-car-user')
            ->assertJsonPath('data.real_estate_province_id', null)
            ->assertJsonPath('data.real_estate_province_name', null)
            ->assertJsonPath('data.real_estate_office_id', null)
            ->assertJsonPath('data.real_estate_office_name', null)
            ->assertJsonPath('data.real_estate_role', null)
            ->assertJsonPath('data.real_estate_role_label', null);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'user_name' => 'updated-car-user',
            'gallery_id' => $gallery->id,
            'permetions_level' => 3,
            'real_estate_province_id' => null,
            'real_estate_office_id' => null,
            'real_estate_role' => null,
        ]);
    }

    public function test_car_admin_can_bootstrap_real_estate_general_manager(): void
    {
        $admin = User::factory()->create([
            'gallery_id' => 0,
            'permetions_level' => 1,
            'real_estate_province_id' => null,
            'real_estate_office_id' => null,
            'real_estate_role' => null,
        ]);

        $this->actingAsSanctum($admin);

        $response = $this->postJson('/api/users', [
            'user_name' => 'real-estate-admin',
            'password' => 'secret',
            'gallery_id' => 0,
            'real_estate_role' => 'general_manager',
            'permetions_level' => 1,
            'salary' => 100,
            'phone' => '0999999997',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.real_estate_role', 'general_manager');

        $this->assertDatabaseHas('users', [
            'user_name' => 'real-estate-admin',
            'real_estate_role' => 'general_manager',
            'permetions_level' => 1,
        ]);
    }

    public function test_real_estate_admin_cannot_view_or_delete_car_user(): void
    {
        $realEstateAdmin = User::factory()->create([
            'gallery_id' => 0,
            'permetions_level' => 1,
            'real_estate_role' => 'general_manager',
        ]);
        $carUser = User::factory()->create([
            'real_estate_province_id' => null,
            'real_estate_office_id' => null,
            'real_estate_role' => null,
        ]);

        $this->actingAsSanctum($realEstateAdmin);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $realEstateAdmin->id])
            ->assertJsonMissing(['id' => $carUser->id]);

        $this->getJson("/api/users/{$carUser->id}")->assertForbidden();
        $this->deleteJson("/api/users/{$carUser->id}")->assertForbidden();

        $this->postJson('/api/users', [
            'user_name' => 'second-car-admin',
            'password' => 'secret',
            'gallery_id' => 0,
            'permetions_level' => 1,
            'salary' => 100,
            'phone' => '0999999996',
        ])
            ->assertUnprocessable()
            ->assertJsonStructure([
                'data' => [
                    'errors' => [
                        'real_estate_role',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', ['id' => $carUser->id]);
        $this->assertDatabaseMissing('users', ['user_name' => 'second-car-admin']);
    }

    public function test_admin_can_delete_user(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Homs',
            'address' => 'Center',
        ]);

        $admin = User::query()->create([
            'user_name' => 'admin',
            'password' => Hash::make('secret'),
            'gallery_id' => 0,
            'permetions_level' => 1,
            'salary' => 0,
            'phone' => '',
        ]);

        $user = User::factory()->create([
            'gallery_id' => $gallery->id,
        ]);

        $this->actingAsSanctum($admin);

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', __('responses.user.delete_success', [], 'ar'));

        $this->assertNull($response->json('data'));
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_user_can_update_own_password(): void
    {
        $user = User::query()->create([
            'user_name' => 'sales-user',
            'password' => Hash::make('old-secret'),
            'gallery_id' => 0,
            'permetions_level' => 4,
            'salary' => 0,
            'phone' => '0999000001',
        ]);

        $this->actingAsSanctum($user);

        $response = $this->putJson("/api/users/{$user->id}/password", [
            'old_password' => 'old-secret',
            'new_password' => 'new-secret',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', __('responses.user.password_updated', [], 'ar'))
            ->assertJsonPath('data.id', $user->id);

        $freshUser = $user->fresh();

        $this->assertNotNull($freshUser);
        $this->assertTrue(Hash::check('new-secret', $freshUser->password));
        $this->assertFalse(Hash::check('old-secret', $freshUser->password));
    }
}
