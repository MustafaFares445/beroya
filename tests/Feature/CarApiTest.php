<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarModel;
use App\Models\Gallery;
use App\Models\Market;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CarApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function carPayload(int $marketId, int $modelId, int $galleryId): array
    {
        return [
            'market_id' => $marketId,
            'model_id' => $modelId,
            'year' => 2023,
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

    public function test_public_cars_response_masks_sensitive_fields(): void
    {
        $market = Market::query()->create([
            'name' => 'BMW',
            'image' => 'bmw.webp',
        ]);
        $model = CarModel::query()->create([
            'name' => 'X5',
            'market_id' => $market->id,
        ]);
        $gallery = Gallery::query()->create([
            'name' => 'Aleppo',
            'address' => 'Main',
        ]);

        $car = Car::query()->create([
            ...$this->carPayload($market->id, $model->id, $gallery->id),
            'image_1' => '',
            'image_2' => '',
            'image_3' => '',
            'image_4' => '',
            'image_5' => '',
            'image_6' => '',
            'car_sale_state' => 1,
        ]);

        $response = $this->getJson('/api/cars');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.plateNumber', '')
            ->assertJsonPath('data.0.owner_name', '')
            ->assertJsonPath('data.0.owner_phone', '');

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            (string) $response->json('data.0.created_at'),
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            (string) $response->json('data.0.updated_at'),
        );

        $showResponse = $this->getJson("/api/cars/{$car->id}");
        $showResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $car->id)
            ->assertJsonPath('data.plateNumber', '')
            ->assertJsonPath('data.owner_name', '')
            ->assertJsonPath('data.owner_phone', '');

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            (string) $showResponse->json('data.created_at'),
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            (string) $showResponse->json('data.updated_at'),
        );
    }

    public function test_user_can_filter_cars_by_market_id_and_model_id_when_market_is_zero(): void
    {
        $marketOne = Market::query()->create([
            'name' => 'Toyota',
            'image' => 'toyota.webp',
        ]);
        $marketTwo = Market::query()->create([
            'name' => 'BMW',
            'image' => 'bmw.webp',
        ]);

        $modelOne = CarModel::query()->create([
            'name' => 'Corolla',
            'market_id' => $marketOne->id,
        ]);
        $modelTwo = CarModel::query()->create([
            'name' => 'X5',
            'market_id' => $marketTwo->id,
        ]);

        $gallery = Gallery::query()->create([
            'name' => 'Damascus',
            'address' => 'Center',
        ]);

        $carOne = Car::query()->create([
            ...$this->carPayload($marketOne->id, $modelOne->id, $gallery->id),
            'image_1' => '',
            'image_2' => '',
            'image_3' => '',
            'image_4' => '',
            'image_5' => '',
            'image_6' => '',
            'car_sale_state' => 1,
        ]);
        $carTwo = Car::query()->create([
            ...$this->carPayload($marketTwo->id, $modelTwo->id, $gallery->id),
            'image_1' => '',
            'image_2' => '',
            'image_3' => '',
            'image_4' => '',
            'image_5' => '',
            'image_6' => '',
            'car_sale_state' => 1,
        ]);

        $marketResponse = $this->getJson('/api/cars?market_id='.$marketOne->id);
        $marketResponse
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $carOne->id);

        $modelResponse = $this->getJson('/api/cars?market_id=0&model_id='.$modelTwo->id);
        $modelResponse
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $carTwo->id);
    }

    public function test_authenticated_user_with_permission_three_can_see_sensitive_fields(): void
    {
        $market = Market::query()->create([
            'name' => 'Audi',
            'image' => 'audi.webp',
        ]);
        $model = CarModel::query()->create([
            'name' => 'A3',
            'market_id' => $market->id,
        ]);
        $gallery = Gallery::query()->create([
            'name' => 'Damascus',
            'address' => 'Airport',
        ]);

        Car::query()->create([
            ...$this->carPayload($market->id, $model->id, $gallery->id),
            'image_1' => '',
            'image_2' => '',
            'image_3' => '',
            'image_4' => '',
            'image_5' => '',
            'image_6' => '',
            'car_sale_state' => 1,
        ]);

        $viewer = User::factory()->create([
            'permetions_level' => 3,
        ]);

        $this->actingAsSanctum($viewer);

        $response = $this->getJson('/api/cars');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.plateNumber', '123456')
            ->assertJsonPath('data.0.owner_name', 'Owner One')
            ->assertJsonPath('data.0.owner_phone', '0999999999');
    }

    public function test_user_with_permission_three_can_create_car_and_upload_image(): void
    {
        $market = Market::query()->create([
            'name' => 'Kia',
            'image' => 'kia.webp',
        ]);
        $model = CarModel::query()->create([
            'name' => 'Sportage',
            'market_id' => $market->id,
        ]);
        $gallery = Gallery::query()->create([
            'name' => 'Homs',
            'address' => 'Center',
        ]);

        $creator = User::factory()->create([
            'permetions_level' => 3,
        ]);

        $this->actingAsSanctum($creator);

        $response = $this->post('/api/cars', [
            ...$this->carPayload($market->id, $model->id, $gallery->id),
            'image1' => UploadedFile::fake()->image('car1.webp'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertNotEmpty($response->json('data.image_1'));
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            (string) $response->json('data.created_at'),
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            (string) $response->json('data.updated_at'),
        );
        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $creator->id,
            'action_type' => 'car.created',
            'target_type' => 'Car',
            'target_id' => $response->json('data.id'),
        ]);
    }

    public function test_user_with_permission_three_can_update_and_delete_car_and_log_activity(): void
    {
        $market = Market::query()->create([
            'name' => 'Toyota',
            'image' => 'toyota.webp',
        ]);
        $model = CarModel::query()->create([
            'name' => 'Corolla',
            'market_id' => $market->id,
        ]);
        $gallery = Gallery::query()->create([
            'name' => 'Hama',
            'address' => 'Center',
        ]);

        $car = Car::query()->create([
            ...$this->carPayload($market->id, $model->id, $gallery->id),
            'image_1' => '',
            'image_2' => '',
            'image_3' => '',
            'image_4' => '',
            'image_5' => '',
            'image_6' => '',
            'car_sale_state' => 1,
        ]);

        $editor = User::factory()->create([
            'permetions_level' => 3,
        ]);

        $this->actingAsSanctum($editor);

        $updatePayload = $this->carPayload($market->id, $model->id, $gallery->id);
        $updatePayload['notes'] = 'updated note';

        $updateResponse = $this->putJson("/api/cars/{$car->id}", $updatePayload);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $editor->id,
            'action_type' => 'car.updated',
            'target_type' => 'Car',
            'target_id' => $car->id,
        ]);

        $deleteResponse = $this->deleteJson("/api/cars/{$car->id}");

        $deleteResponse
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $editor->id,
            'action_type' => 'car.deleted',
            'target_type' => 'Car',
            'target_id' => $car->id,
        ]);
    }

    public function test_user_with_permission_four_can_update_sale_state_only(): void
    {
        $market = Market::query()->create([
            'name' => 'Hyundai',
            'image' => 'hyundai.webp',
        ]);
        $model = CarModel::query()->create([
            'name' => 'Elantra',
            'market_id' => $market->id,
        ]);
        $gallery = Gallery::query()->create([
            'name' => 'Latakia',
            'address' => 'Sea',
        ]);

        $car = Car::query()->create([
            ...$this->carPayload($market->id, $model->id, $gallery->id),
            'image_1' => '',
            'image_2' => '',
            'image_3' => '',
            'image_4' => '',
            'image_5' => '',
            'image_6' => '',
            'car_sale_state' => 1,
        ]);

        $salesUser = User::factory()->create([
            'permetions_level' => 4,
        ]);

        $this->actingAsSanctum($salesUser);

        $response = $this->putJson("/api/cars/{$car->id}/sale-state", [
            'sale_state' => 2,
            'edit_type' => 'prim',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame(2, (int) $car->fresh()->car_sale_state);
        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $salesUser->id,
            'action_type' => 'car.sale_state_updated',
            'target_type' => 'Car',
            'target_id' => $car->id,
        ]);
    }

    public function test_latest_cars_endpoint_returns_newest_forty_non_sold_cars(): void
    {
        $market = Market::query()->create([
            'name' => 'Toyota',
            'image' => 'toyota.webp',
        ]);
        $model = CarModel::query()->create([
            'name' => 'Corolla',
            'market_id' => $market->id,
        ]);
        $gallery = Gallery::query()->create([
            'name' => 'Homs',
            'address' => 'Center',
        ]);

        $baseTime = Carbon::parse('2026-07-03 12:00:00');
        $latestReturnedId = null;

        for ($index = 1; $index <= 41; $index++) {
            $car = Car::query()->create([
                ...$this->carPayload($market->id, $model->id, $gallery->id),
                'year' => 2020 + $index,
                'plateNumber' => (string) $index,
                'image_1' => '',
                'image_2' => '',
                'image_3' => '',
                'image_4' => '',
                'image_5' => '',
                'image_6' => '',
                'car_sale_state' => $index === 41 ? 4 : 1,
            ]);

            $createdAt = $baseTime->copy()->subMinutes(41 - $index);
            Car::query()
                ->whereKey($car->id)
                ->update([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

            if ($index === 40) {
                $latestReturnedId = $car->id;
            }
        }

        $response = $this->getJson('/api/cars/latest');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(40, 'data')
            ->assertJsonPath('data.0.id', $latestReturnedId);

        $ids = array_column($response->json('data'), 'id');
        $this->assertNotContains(41, $ids);
    }
}
