<?php

namespace Tests\Feature;

use App\Models\CarModel;
use App\Models\Market;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarModelApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_read_models(): void
    {
        $market = Market::query()->create([
            'name' => 'Kia',
            'image' => 'kia.webp',
        ]);

        CarModel::query()->create([
            'name' => 'Sportage',
            'market_id' => $market->id,
        ]);

        $response = $this->getJson('/api/car-models');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');

        $carModel = CarModel::query()->firstOrFail();

        $this->getJson("/api/car-models/{$carModel->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $carModel->id)
            ->assertJsonPath('data.name', 'Sportage')
            ->assertJsonPath('data.market_id', $market->id);
    }

    public function test_user_with_permission_level_three_can_create_model(): void
    {
        $market = Market::query()->create([
            'name' => 'BMW',
            'image' => 'bmw.webp',
        ]);

        $user = User::factory()->create([
            'permetions_level' => 3,
        ]);

        $this->actingAsSanctum($user);

        $response = $this->postJson('/api/car-models', [
            'name' => 'X5',
            'market_id' => $market->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'X5');
    }

    public function test_manager_can_update_model(): void
    {
        $market = Market::query()->create([
            'name' => 'Audi',
            'image' => 'audi.webp',
        ]);

        $carModel = CarModel::query()->create([
            'name' => 'A3',
            'market_id' => $market->id,
        ]);

        $manager = User::factory()->create([
            'permetions_level' => 2,
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->putJson("/api/car-models/{$carModel->id}", [
            'name' => 'A4',
            'market_id' => $market->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'A4');
    }

    public function test_manager_can_delete_model(): void
    {
        $market = Market::query()->create([
            'name' => 'Audi',
            'image' => 'audi.webp',
        ]);

        $carModel = CarModel::query()->create([
            'name' => 'A6',
            'market_id' => $market->id,
        ]);

        $manager = User::factory()->create([
            'permetions_level' => 2,
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->deleteJson("/api/car-models/{$carModel->id}");

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $carModel->id);

        $this->assertDatabaseMissing('models', [
            'id' => $carModel->id,
        ]);
    }

    public function test_regular_user_cannot_create_model(): void
    {
        $market = Market::query()->create([
            'name' => 'Subaru',
            'image' => 'subaru.webp',
        ]);

        $user = User::factory()->create([
            'permetions_level' => 4,
        ]);

        $this->actingAsSanctum($user);

        $response = $this->postJson('/api/car-models', [
            'name' => 'Impreza',
            'market_id' => $market->id,
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');
    }
}
