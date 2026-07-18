<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_legacy_response_shape_and_sanctum_token(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Aleppo',
            'address' => 'Main Street',
        ]);

        User::query()->create([
            'user_name' => 'admin',
            'password' => Hash::make('secret'),
            'gallery_id' => $gallery->id,
            'permetions_level' => 1,
            'salary' => 0,
            'phone' => '',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'secret',
            'currentWeekNum' => 20,
            'currentYear' => 2026,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', __('responses.success', [], 'ar'))
            ->assertJsonPath('data.real_estate_role_label', null)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'user_name',
                    'gallery_id',
                    'real_estate_role_label',
                    'permetions_level',
                    'salary',
                    'phone',
                    'token',
                    'token_expiry',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertSame(1, PersonalAccessToken::query()->count());
    }

    public function test_login_accepts_admin_user_with_password_0000(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Aleppo',
            'address' => 'Main Street',
        ]);

        User::query()->create([
            'user_name' => 'admin',
            'password' => Hash::make('0000'),
            'gallery_id' => $gallery->id,
            'permetions_level' => 1,
            'salary' => 0,
            'phone' => '',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => '0000',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', __('responses.success', [], 'ar'))
            ->assertJsonPath('data.user_name', 'admin')
            ->assertJsonPath('data.real_estate_role_label', null);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertSame(1, PersonalAccessToken::query()->count());
    }

    public function test_logout_revokes_sanctum_token_and_returns_success(): void
    {
        $user = User::factory()->create([
            'permetions_level' => 1,
        ]);

        $token = $this->actingAsSanctum($user);

        $response = $this->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', __('responses.auth.logout_success', [], 'ar'))
            ->assertJsonPath('data.id', $user->id);

        $this->assertNull(PersonalAccessToken::findToken($token));
    }

    public function test_login_rehashes_legacy_plain_password(): void
    {
        User::query()->create([
            'user_name' => 'legacy-user',
            'password' => 'plain-secret',
            'gallery_id' => 0,
            'permetions_level' => 1,
            'salary' => 0,
            'phone' => '',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'legacy-user',
            'password' => 'plain-secret',
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');

        $user = User::query()->where('user_name', 'legacy-user')->firstOrFail();
        $this->assertTrue(Hash::check('plain-secret', $user->password));
    }
}
