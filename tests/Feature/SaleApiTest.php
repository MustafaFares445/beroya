<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Gallery;
use App\Models\Market;
use App\Models\Sale;
use App\Models\User;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{gallery: Gallery, car: Car, week: Week, seller: User}
     */
    private function createSaleContext(): array
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

        $car = Car::query()->create([
            'market_id' => $market->id,
            'model_id' => $model->id,
            'year' => '2023',
            'gasoline' => 'gasoline',
            'engine' => '2.0',
            'transmission' => 'automatic',
            'color' => 'black',
            'distance' => '10000',
            'imported' => 'yes',
            'spray' => 'none',
            'status' => 'available',
            'description' => 'Clean',
            'plateNumber' => '123456',
            'notes' => 'note',
            'price' => 20000,
            'possession' => 'owner',
            'owner_name' => 'Owner',
            'owner_phone' => '0999999999',
            'gallery_id' => $gallery->id,
            'image_1' => '',
            'image_2' => '',
            'image_3' => '',
            'image_4' => '',
            'image_5' => '',
            'image_6' => '',
            'car_sale_state' => 1,
        ]);

        $week = Week::factory()->create([
            'week_num' => 1,
            'year' => 2026,
            'start_date' => '2026-01-02',
            'end_date' => '2026-01-08',
        ]);

        $seller = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 4,
            'salary' => 500,
        ]);

        return compact('gallery', 'car', 'week', 'seller');
    }

    /**
     * @return array<string, scalar>
     */
    private function salePayload(int $carId, int $userId, string $date = '2026-01-05'): array
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
            'date' => $date,
            'user_id' => $userId,
        ];
    }

    public function test_user_can_create_hold_sale(): void
    {
        $context = $this->createSaleContext();
        $this->actingAsSanctum($context['seller']);

        $response = $this->postJson(
            '/api/sales',
            $this->salePayload($context['car']->id, $context['seller']->id),
        );

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'hold')
            ->assertJsonPath('data.approved', '0');
    }

    public function test_user_can_create_done_sale_when_buyer_commission_is_non_zero(): void
    {
        $context = $this->createSaleContext();
        $this->actingAsSanctum($context['seller']);

        $payload = $this->salePayload($context['car']->id, $context['seller']->id);
        $payload['buyer_comiss'] = 100;

        $response = $this->postJson('/api/sales', $payload);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', 'done')
            ->assertJsonPath('data.approved', '1');
    }

    public function test_hold_sales_are_filtered_by_gallery(): void
    {
        $context = $this->createSaleContext();
        $this->actingAsSanctum($context['seller']);

        $otherGallery = Gallery::query()->create([
            'name' => 'Damascus',
            'address' => 'Center',
        ]);

        $otherSeller = User::factory()->create([
            'gallery_id' => $otherGallery->id,
            'permetions_level' => 4,
        ]);

        Sale::factory()->create([
            'status' => 'hold',
            'week_id' => $context['week']->id,
            'user_id' => $context['seller']->id,
            'date' => '2026-01-05',
        ]);

        Sale::factory()->create([
            'status' => 'hold',
            'week_id' => $context['week']->id,
            'user_id' => $otherSeller->id,
            'date' => '2026-01-05',
        ]);

        $response = $this->getJson('/api/sales?status=hold&gallery_id='.$context['gallery']->id);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_manager_can_complete_sale_and_update_car_state(): void
    {
        $context = $this->createSaleContext();

        $manager = User::factory()->create([
            'gallery_id' => $context['gallery']->id,
            'permetions_level' => 2,
        ]);

        $sale = Sale::factory()->create([
            'status' => 'hold',
            'week_id' => $context['week']->id,
            'user_id' => $context['seller']->id,
            'car_id' => $context['car']->id,
            'date' => '2026-01-05',
            'user_comiss' => 150,
        ]);

        Account::factory()->create([
            'user_id' => $context['seller']->id,
            'week_id' => $context['week']->id,
            'year' => '2026',
            'user_gallery' => $context['gallery']->name,
            'salary' => 500,
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->putJson("/api/sales/{$sale->id}/complete", [
            'user_comiss' => 200,
            'owner_comiss' => 0,
            'owner_comiss_payed' => 0,
            'buyer_comiss' => 0,
            'buyer_comiss_payed' => 0,
            'employee_name' => 'Manager',
            'user_note' => 'Completed',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', 'done');

        $this->assertDatabaseHas('cars', [
            'id' => $context['car']->id,
            'car_sale_state' => 3,
        ]);

        $this->assertDatabaseHas('accountants', [
            'user_id' => $context['seller']->id,
            'week_id' => $context['week']->id,
            'sales_count' => 1,
            'sales_amount' => 200,
        ]);
    }

    public function test_sales_user_cannot_complete_sale(): void
    {
        $context = $this->createSaleContext();
        $this->actingAsSanctum($context['seller']);

        $sale = Sale::factory()->create([
            'status' => 'hold',
            'week_id' => $context['week']->id,
            'user_id' => $context['seller']->id,
            'car_id' => $context['car']->id,
            'date' => '2026-01-05',
        ]);

        $response = $this->putJson("/api/sales/{$sale->id}/complete", [
            'user_comiss' => 200,
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('data', 'your computer harmly damaged');
    }

    public function test_manager_can_delete_sale_order(): void
    {
        $context = $this->createSaleContext();

        $manager = User::factory()->create([
            'gallery_id' => $context['gallery']->id,
            'permetions_level' => 2,
        ]);

        $sale = Sale::factory()->create([
            'status' => 'hold',
            'week_id' => $context['week']->id,
            'user_id' => $context['seller']->id,
            'car_id' => $context['car']->id,
            'date' => '2026-01-05',
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->deleteJson("/api/sale-orders/{$sale->id}");

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }
}
