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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

        $saleId = (int) $response->json('data.id');

        $this->assertNotNull($response->json('data.requested_at'));
        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $context['seller']->id,
            'action_type' => 'sale.created',
            'target_type' => 'Sale',
            'target_id' => $saleId,
        ]);

        $this->getJson("/api/sales/{$saleId}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $saleId)
            ->assertJsonPath('data.status', 'hold')
            ->assertJsonPath('data.requested_at', $response->json('data.requested_at'));
    }

    public function test_sale_media_is_stored_privately_and_returned_as_temporary_links(): void
    {
        Storage::fake('local');

        $context = $this->createSaleContext();
        $this->actingAsSanctum($context['seller']);

        $payload = $this->salePayload($context['car']->id, $context['seller']->id);
        $payload['owner_id_image'] = UploadedFile::fake()->image('owner-id.jpg');
        $payload['buyer_id_image'] = UploadedFile::fake()->image('buyer-id.jpg');
        $payload['contract_image'] = UploadedFile::fake()->image('contract.jpg');

        $response = $this->post('/api/sales', $payload, [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $sale = Sale::query()->findOrFail($response->json('data.id'));
        $expectedUrlPrefix = rtrim((string) config('app.url'), '/').'/sales/';

        foreach (['owner_id_image', 'buyer_id_image', 'contract_image'] as $field) {
            $this->assertStringStartsWith('sales/', $sale->{$field});
            Storage::disk('local')->assertExists($sale->{$field});
            $this->assertStringStartsWith($expectedUrlPrefix, $response->json('data.'.$field));
            $this->assertStringContainsString('expiration=', $response->json('data.'.$field));
        }

        $this->assertFileDoesNotExist(public_path('data/uploads/sales/'.basename($sale->owner_id_image)));
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

        $this->assertNotNull($response->json('data.requested_at'));
        $this->assertDatabaseHas('cars', [
            'id' => $context['car']->id,
            'car_sale_state' => 3,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $context['seller']->id,
            'action_type' => 'sale.created',
            'target_type' => 'Sale',
            'target_id' => $response->json('data.id'),
        ]);
    }

    public function test_user_can_update_hold_sale(): void
    {
        $context = $this->createSaleContext();
        $this->actingAsSanctum($context['seller']);

        $sale = Sale::query()->create([
            ...$this->salePayload($context['car']->id, $context['seller']->id, '2026-01-05'),
            'week_id' => $context['week']->id,
            'owner_id_image' => '',
            'buyer_id_image' => '',
            'contract_image' => '',
            'status' => 'hold',
            'approved' => '0',
            'requested_at' => '2026-01-05 09:00:00',
            'approved_at' => null,
            'completed_at' => null,
        ]);

        $payload = $this->salePayload($context['car']->id, $context['seller']->id, '2026-01-06');
        $payload['user_note'] = 'Updated note';
        $payload['price'] = 27000;

        $response = $this->putJson("/api/sales/{$sale->id}", $payload);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $sale->id)
            ->assertJsonPath('data.user_note', 'Updated note')
            ->assertJsonPath('data.price', 27000)
            ->assertJsonPath('data.status', 'hold')
            ->assertJsonPath('data.requested_at', '2026-01-05 09:00:00');

        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $context['seller']->id,
            'action_type' => 'sale.updated',
            'target_type' => 'Sale',
            'target_id' => $sale->id,
        ]);
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

    public function test_hold_sales_are_filtered_by_user_id(): void
    {
        $context = $this->createSaleContext();
        $this->actingAsSanctum($context['seller']);

        $otherSeller = User::factory()->create([
            'gallery_id' => $context['gallery']->id,
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

        $response = $this->getJson('/api/sales?status=hold&user_id='.$otherSeller->id);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', $otherSeller->id);
    }

    public function test_hold_sales_are_sorted_by_requested_time_descending(): void
    {
        $context = $this->createSaleContext();
        $this->actingAsSanctum($context['seller']);

        Sale::query()->create([
            ...$this->salePayload($context['car']->id, $context['seller']->id),
            'week_id' => $context['week']->id,
            'owner_id_image' => '',
            'buyer_id_image' => '',
            'contract_image' => '',
            'status' => 'hold',
            'approved' => '0',
            'requested_at' => '2026-01-05 09:00:00',
            'approved_at' => null,
            'completed_at' => null,
        ]);

        $secondSale = Sale::query()->create([
            ...$this->salePayload($context['car']->id, $context['seller']->id),
            'week_id' => $context['week']->id,
            'owner_id_image' => '',
            'buyer_id_image' => '',
            'contract_image' => '',
            'status' => 'hold',
            'approved' => '0',
            'requested_at' => '2026-01-05 10:00:00',
            'approved_at' => null,
            'completed_at' => null,
        ]);

        $response = $this->getJson('/api/sales?status=hold&gallery_id='.$context['gallery']->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $secondSale->id)
            ->assertJsonPath('data.0.requested_at', '2026-01-05 10:00:00');
    }

    public function test_manager_can_approve_sale_order_and_store_approval_timestamp(): void
    {
        $context = $this->createSaleContext();

        $manager = User::factory()->create([
            'gallery_id' => $context['gallery']->id,
            'permetions_level' => 2,
        ]);

        $sale = Sale::factory()->create([
            'status' => 'hold',
            'approved' => '0',
            'week_id' => $context['week']->id,
            'user_id' => $context['seller']->id,
            'car_id' => $context['car']->id,
            'date' => '2026-01-05',
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->putJson("/api/sales/{$sale->id}/approve-order", [
            'ownerName' => 'Approved Owner',
            'ownerPhone' => '0999111111',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.approved', '1')
            ->assertJsonPath('data.owner_name', 'Approved Owner')
            ->assertJsonPath('data.owner_phone', '0999111111');

        $this->assertNotNull($response->json('data.approved_at'));
        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $manager->id,
            'action_type' => 'sale.approved',
            'target_type' => 'Sale',
            'target_id' => $sale->id,
        ]);
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

        $this->assertNotNull($response->json('data.completed_at'));
        $this->assertDatabaseHas('cars', [
            'id' => $context['car']->id,
            'car_sale_state' => 3,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $manager->id,
            'action_type' => 'sale.completed',
            'target_type' => 'Sale',
            'target_id' => $sale->id,
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
        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $manager->id,
            'action_type' => 'sale.deleted',
            'target_type' => 'Sale',
            'target_id' => $sale->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $manager->id,
            'action_type' => 'car.sale_state_updated',
            'target_type' => 'Car',
            'target_id' => $context['car']->id,
        ]);
    }

    public function test_manager_can_update_sale_installment_contract(): void
    {
        $context = $this->createSaleContext();

        $manager = User::factory()->create([
            'gallery_id' => $context['gallery']->id,
            'permetions_level' => 2,
        ]);

        $sale = Sale::factory()->create([
            'status' => 'hold',
            'approved' => '0',
            'contract_type' => 'cash',
            'week_id' => $context['week']->id,
            'user_id' => $context['seller']->id,
            'car_id' => $context['car']->id,
            'date' => '2026-01-05',
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->putJson("/api/sales/{$sale->id}/installment-contract", [
            'installment_count' => 24,
            'installment_amount' => 1000,
            'installment_start_date' => '2026-02-01',
            'installment_end_date' => '2028-01-01',
            'installment_note' => 'Monthly installments',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.contract_type', 'installment')
            ->assertJsonPath('data.installment_count', 24)
            ->assertJsonPath('data.installment_amount', 1000)
            ->assertJsonPath('data.installment_start_date', '2026-02-01')
            ->assertJsonPath('data.installment_end_date', '2028-01-01')
            ->assertJsonPath('data.installment_note', 'Monthly installments');

        $this->assertDatabaseHas('activity_logs', [
            'actor_user_id' => $manager->id,
            'action_type' => 'sale.installment_contract.updated',
            'target_type' => 'Sale',
            'target_id' => $sale->id,
        ]);
    }
}
