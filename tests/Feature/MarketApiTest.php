<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MarketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_read_markets(): void
    {
        Market::query()->create([
            'name' => 'BMW',
            'image' => 'bmw.webp',
        ]);

        $response = $this->getJson('/api/markets');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_user_with_permission_level_three_can_create_market(): void
    {
        $user = User::factory()->create([
            'permetions_level' => 3,
        ]);

        $this->actingAsSanctum($user);

        $response = $this->post('/api/markets', [
            'name' => 'Kia',
            'image' => UploadedFile::fake()->image('kia.webp'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Kia');
    }

    public function test_manager_can_update_market(): void
    {
        $market = Market::query()->create([
            'name' => 'Old',
            'image' => 'old.webp',
        ]);

        $manager = User::factory()->create([
            'permetions_level' => 2,
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->post("/api/markets/{$market->id}", [
            '_method' => 'PUT',
            'name' => 'New',
            'image' => UploadedFile::fake()->image('new.webp'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'New');
    }

    public function test_regular_user_cannot_create_market(): void
    {
        $user = User::factory()->create([
            'permetions_level' => 4,
        ]);

        $this->actingAsSanctum($user);

        $response = $this->post('/api/markets', [
            'name' => 'Hyundai',
            'image' => UploadedFile::fake()->image('hyundai.webp'),
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');
    }
}
