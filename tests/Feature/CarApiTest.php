<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarModel;
use App\Models\Gallery;
use App\Models\Market;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

        $response = $this->getJson('/api/cars');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.plateNumber', '')
            ->assertJsonPath('data.0.owner_name', '')
            ->assertJsonPath('data.0.owner_phone', '');
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
    }
}
