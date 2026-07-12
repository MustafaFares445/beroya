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
        $order = Order::query()->create($this->orderPayload());

        $user = User::factory()->create([
            'permetions_level' => 4,
        ]);

        $this->actingAsSanctum($user);

        $response = $this->getJson('/api/orders');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.checked', 0);

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            (string) $response->json('data.0.created_at'),
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            (string) $response->json('data.0.updated_at'),
        );

        $this->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.client_name', 'Client One')
            ->assertJsonPath('data.order_state', 'open')
            ->assertJsonPath('data.checked', 0);
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
            ->assertJsonPath('data.client_name', 'Client One')
            ->assertJsonPath('data.checked', 0);

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            (string) $response->json('data.created_at'),
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            (string) $response->json('data.updated_at'),
        );
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
            ->assertJsonPath('data.order_notes', 'Updated note')
            ->assertJsonPath('data.checked', 0);

        $deleteResponse = $this->deleteJson("/api/orders/{$order->id}");

        $deleteResponse
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_manager_can_approve_order(): void
    {
        $order = Order::factory()->create([
            'order_state' => 'open',
            'approved_at' => null,
            'rejected_at' => null,
            'reviewed_by_user_id' => null,
            'reject_reason' => null,
        ]);

        $manager = User::factory()->create([
            'permetions_level' => 2,
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->putJson("/api/orders/{$order->id}/approve");

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.order_state', 'approved')
            ->assertJsonPath('data.reviewed_by_user_id', $manager->id);

        $this->assertNotNull($response->json('data.approved_at'));
        $this->assertNull($response->json('data.rejected_at'));
        $this->assertNull($response->json('data.reject_reason'));
        $this->assertSame(0, (int) $response->json('data.checked'));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_state' => 'approved',
            'reviewed_by_user_id' => $manager->id,
        ]);
    }

    public function test_manager_can_reject_order(): void
    {
        $order = Order::factory()->create([
            'order_state' => 'open',
            'approved_at' => null,
            'rejected_at' => null,
            'reviewed_by_user_id' => null,
            'reject_reason' => null,
        ]);

        $manager = User::factory()->create([
            'permetions_level' => 1,
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->putJson("/api/orders/{$order->id}/reject", [
            'reject_reason' => 'Missing car details',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.order_state', 'rejected')
            ->assertJsonPath('data.reviewed_by_user_id', $manager->id)
            ->assertJsonPath('data.reject_reason', 'Missing car details');

        $this->assertNotNull($response->json('data.rejected_at'));
        $this->assertNull($response->json('data.approved_at'));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_state' => 'rejected',
            'reviewed_by_user_id' => $manager->id,
            'reject_reason' => 'Missing car details',
        ]);
    }

    public function test_user_with_permission_level_three_cannot_approve_order(): void
    {
        $order = Order::factory()->create([
            'order_state' => 'open',
        ]);

        $user = User::factory()->create([
            'permetions_level' => 3,
        ]);

        $this->actingAsSanctum($user);

        $response = $this->putJson("/api/orders/{$order->id}/approve");

        $response
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');
    }

    public function test_user_with_permission_level_three_can_toggle_checked_flag_without_changing_order_state(): void
    {
        $order = Order::factory()->create([
            'order_state' => 'open',
            'checked' => 0,
        ]);

        $user = User::factory()->create([
            'permetions_level' => 3,
        ]);

        $this->actingAsSanctum($user);

        $checkResponse = $this->putJson("/api/orders/{$order->id}/checked", [
            'checked' => 1,
        ]);

        $checkResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.checked', 1)
            ->assertJsonPath('data.order_state', 'open');

        $uncheckResponse = $this->putJson("/api/orders/{$order->id}/checked", [
            'checked' => 0,
        ]);

        $uncheckResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.checked', 0)
            ->assertJsonPath('data.order_state', 'open');
    }

    public function test_regular_user_cannot_update_checked_flag(): void
    {
        $order = Order::factory()->create([
            'order_state' => 'open',
            'checked' => 0,
        ]);

        $user = User::factory()->create([
            'permetions_level' => 4,
        ]);

        $this->actingAsSanctum($user);

        $response = $this->putJson("/api/orders/{$order->id}/checked", [
            'checked' => 1,
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', 'your computer harmly damaged');
    }
}
