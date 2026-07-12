<?php

namespace Tests\Feature;

use App\Models\CarModel;
use App\Models\Gallery;
use App\Models\Market;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{gallery: Gallery, market: Market, model: CarModel, manager: User}
     */
    private function createContext(): array
    {
        $gallery = Gallery::query()->create([
            'name' => 'Aleppo',
            'address' => 'Main',
        ]);

        $market = Market::query()->create([
            'name' => 'BMW',
            'image' => 'bmw.webp',
        ]);

        $model = CarModel::query()->create([
            'name' => 'X5',
            'market_id' => $market->id,
        ]);

        $manager = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 2,
        ]);

        return compact('gallery', 'market', 'model', 'manager');
    }

    /**
     * @return array<string, mixed>
     */
    private function carPayload(int $marketId, int $modelId, int $galleryId): array
    {
        return [
            'market_id' => $marketId,
            'model_id' => $modelId,
            'year' => 2024,
            'gasoline' => 'gasoline',
            'engine' => '2.0',
            'transmission' => 'automatic',
            'color' => 'black',
            'distance' => '10000',
            'imported' => 'yes',
            'spray' => 'none',
            'status' => 'available',
            'description' => 'Clean condition',
            'plateNumber' => '123456',
            'notes' => 'new car',
            'price' => 20000,
            'possession' => 'owner',
            'owner_name' => 'Owner One',
            'owner_phone' => '0999999999',
            'gallery_id' => $galleryId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function salePayload(int $carId, int $userId): array
    {
        return [
            'car_id' => $carId,
            'car_brand' => 'BMW',
            'car_model' => 'X5',
            'car_number' => '123456',
            'car_name' => 'BMW X5',
            'price' => 25000,
            'employee_name' => 'Employee',
            'user_comiss' => 200,
            'user_note' => 'Note',
            'owner_name' => 'Owner',
            'owner_phone' => '0999999999',
            'owner_comiss' => 0,
            'owner_comiss_payed' => 0,
            'buyer_name' => 'Buyer',
            'buyer_phone' => 912345678,
            'buyer_comiss' => 0,
            'buyer_comiss_payed' => 0,
            'date' => now()->toDateString(),
            'user_id' => $userId,
        ];
    }

    public function test_manager_can_list_and_filter_activity_logs(): void
    {
        $context = $this->createContext();
        $this->actingAsSanctum($context['manager']);

        $carResponse = $this->postJson('/api/cars', $this->carPayload(
            $context['market']->id,
            $context['model']->id,
            $context['gallery']->id,
        ));

        $carResponse
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $saleResponse = $this->postJson('/api/sales', $this->salePayload(
            (int) $carResponse->json('data.id'),
            $context['manager']->id,
        ));

        $saleResponse
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $response = $this->getJson('/api/activity-logs?user_id='.$context['manager']->id.'&gallery_id='.$context['gallery']->id.'&action_type=sale.created&date='.now()->toDateString());

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action_type', 'sale.created')
            ->assertJsonPath('data.0.actor_user_name', $context['manager']->user_name)
            ->assertJsonPath('data.0.gallery_id', $context['gallery']->id);
    }

    public function test_regular_user_cannot_list_activity_logs(): void
    {
        $context = $this->createContext();

        $regularUser = User::factory()->create([
            'gallery_id' => $context['gallery']->id,
            'permetions_level' => 4,
        ]);

        $this->actingAsSanctum($regularUser);

        $response = $this->getJson('/api/activity-logs');

        $response
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');
    }
}
