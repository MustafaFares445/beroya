<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarModel;
use App\Models\Gallery;
use App\Models\Market;
use App\Models\User;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, User>
     */
    private function createOrderContext(): array
    {
        $creator = User::factory()->create([
            'permetions_level' => 3,
        ]);

        $manager = User::factory()->create([
            'permetions_level' => 2,
        ]);

        return compact('creator', 'manager');
    }

    /**
     * @return array{gallery: Gallery, car: Car, week: Week, seller: User, manager: User}
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

        $manager = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 2,
        ]);

        return compact('gallery', 'car', 'week', 'seller', 'manager');
    }

    /**
     * @return array<string, scalar>
     */
    private function orderPayload(): array
    {
        return [
            'client_name' => 'Client One',
            'client_phone' => '0999999999',
            'car_market' => 'BMW',
            'car_model' => 'X5',
            'year' => '2023',
            'price_low' => 10000,
            'price_high' => 20000,
            'order_state' => 'open',
            'order_notes' => 'Need low mileage',
            'user_name' => 'employee',
            'gallery_name' => 'Aleppo',
        ];
    }

    /**
     * @return array<string, scalar>
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
            'date' => '2026-01-05',
            'user_id' => $userId,
        ];
    }

    public function test_order_submission_notifications_can_be_read_and_deleted(): void
    {
        $context = $this->createOrderContext();

        Sanctum::actingAs($context['creator']);

        $response = $this->postJson('/api/orders', $this->orderPayload());

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.order_state', 'open');

        $notification = $context['manager']->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);
        $this->assertSame('order', $notification->data['category']);
        $this->assertSame('created', $notification->data['event']);
        $this->assertSame('New order submitted', $notification->data['title']);
        $this->assertSame(1, $context['manager']->unreadNotifications()->count());

        Sanctum::actingAs($context['manager']);

        $indexResponse = $this->getJson('/api/notifications');

        $indexResponse
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame(1, $indexResponse->json('data.unread_count'));
        $this->assertSame('order', $indexResponse->json('data.notifications.0.category'));
        $this->assertSame('created', $indexResponse->json('data.notifications.0.event'));

        $markReadResponse = $this->putJson("/api/notifications/{$notification->id}/read");

        $markReadResponse
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertNotNull($markReadResponse->json('data.read_at'));

        $afterReadResponse = $this->getJson('/api/notifications');

        $afterReadResponse
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        $deleteResponse = $this->deleteJson("/api/notifications/{$notification->id}");

        $deleteResponse
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function test_sale_submission_creates_pending_sale_notification(): void
    {
        $context = $this->createSaleContext();

        Sanctum::actingAs($context['seller']);

        $response = $this->postJson('/api/sales', $this->salePayload(
            $context['car']->id,
            $context['seller']->id,
        ));

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'hold');

        $notification = $context['manager']->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);
        $this->assertSame('sale', $notification->data['category']);
        $this->assertSame('created', $notification->data['event']);
        $this->assertSame('Pending sale created', $notification->data['title']);
        $this->assertSame('hold', $notification->data['meta']['status']);
        $this->assertSame('cash', $notification->data['meta']['contract_type']);
        $this->assertSame(
            sprintf('Sale #%d is waiting for approval.', $response->json('data.id')),
            $notification->data['body'],
        );
    }
}
