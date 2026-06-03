<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeekApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_permission_level_four_can_list_weeks(): void
    {
        Week::factory()->create([
            'week_num' => 2,
            'year' => 2026,
        ]);

        $user = User::factory()->create([
            'permetions_level' => 4,
        ]);

        $this->actingAsSanctum($user);

        $response = $this->getJson('/api/weeks');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_regular_user_cannot_list_weeks(): void
    {
        $user = User::factory()->create([
            'permetions_level' => 5,
        ]);

        $this->actingAsSanctum($user);

        $response = $this->getJson('/api/weeks');

        $response
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');
    }
}
