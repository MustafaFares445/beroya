<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_user_with_permission_level_four_can_list_orders(): void
    {
        Order::query()->create($this->orderPayload());

        $user = User::factory()->create([
            'permetions_level' => 4,
        ]);

        $this->actingAsSanctum($user);

        $response = $this->getJson('/api/orders');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');
    }

    public function test_user_with_permission_level_three_can_create_order(): void
    {
        $user = User::factory()->create([
            'permetions_level' => 3,
        ]);

        $this->actingAsSanctum($user);

        $response = $this->postJson('/api/orders', $this->orderPayload());

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.client_name', 'Client One');
    }

    public function test_regular_user_cannot_create_order(): void
    {
        $user = User::factory()->create([
            'permetions_level' => 4,
        ]);

        $this->actingAsSanctum($user);

        $response = $this->postJson('/api/orders', $this->orderPayload());

        $response
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');
    }

    public function test_user_with_permission_level_three_can_update_and_delete_order(): void
    {
        $order = Order::query()->create($this->orderPayload());

        $user = User::factory()->create([
            'permetions_level' => 3,
        ]);

        $this->actingAsSanctum($user);

        $updatePayload = $this->orderPayload();
        $updatePayload['order_notes'] = 'Updated note';

        $updateResponse = $this->putJson("/api/orders/{$order->id}", $updatePayload);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.order_notes', 'Updated note');

        $deleteResponse = $this->deleteJson("/api/orders/{$order->id}");

        $deleteResponse
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }
}
