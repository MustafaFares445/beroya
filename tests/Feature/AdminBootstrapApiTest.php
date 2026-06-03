<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBootstrapApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_is_disabled_by_default(): void
    {
        config(['legacy.allow_admin_init' => false]);

        $response = $this->postJson('/api/auth/bootstrap-admin');

        $response
            ->assertStatus(400)
            ->assertJsonPath('status', 'failure');
    }

    public function test_bootstrap_fails_when_users_already_exist(): void
    {
        config(['legacy.allow_admin_init' => true]);

        User::factory()->create();

        $response = $this->postJson('/api/auth/bootstrap-admin');

        $response
            ->assertStatus(400)
            ->assertJsonPath('status', 'failure');
    }

    public function test_bootstrap_creates_admin_financial_and_guest_users(): void
    {
        config(['legacy.allow_admin_init' => true]);

        $response = $this->postJson('/api/auth/bootstrap-admin');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.admin.user_name', 'admin')
            ->assertJsonPath('data.financial.user_name', 'المدير المالي')
            ->assertJsonPath('data.guest.user_name', 'guest');

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseHas('users', [
            'user_name' => 'admin',
            'permetions_level' => 1,
            'gallery_id' => 0,
        ]);
        $this->assertDatabaseHas('users', [
            'user_name' => 'guest',
            'permetions_level' => 111,
        ]);
    }
}
