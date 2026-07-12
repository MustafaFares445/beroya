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
