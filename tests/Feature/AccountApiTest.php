<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Bonus;
use App\Models\Deduction;
use App\Models\Gallery;
use App\Models\User;
use App\Models\Week;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_accounts_returns_failure_when_empty(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Aleppo',
            'address' => 'Main',
        ]);

        Week::factory()->create([
            'week_num' => 1,
            'year' => 2026,
        ]);

        $manager = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 2,
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->getJson('/api/accounts/weekly?week=1&year=2026&gallery_id='.$gallery->id);

        $response
            ->assertStatus(400)
            ->assertJsonPath('status', 'failure')
            ->assertJsonPath('data', []);
    }

    public function test_weekly_accounts_returns_accounts_for_gallery(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Aleppo',
            'address' => 'Main',
        ]);

        $week = Week::factory()->create([
            'week_num' => 1,
            'year' => 2026,
        ]);

        $employee = User::factory()->create([
            'gallery_id' => $gallery->id,
            'salary' => 700,
        ]);

        Account::factory()->create([
            'user_id' => $employee->id,
            'week_id' => $week->id,
            'year' => '2026',
            'user_gallery' => $gallery->name,
            'user_name' => $employee->user_name,
            'salary' => 700,
        ]);

        $manager = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 2,
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->getJson('/api/accounts/weekly?week=1&year=2026&gallery_id='.$gallery->id);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.salary', 700);
    }

    public function test_account_details_merge_deductions_and_bonuses(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Aleppo',
            'address' => 'Main',
        ]);

        $week = Week::factory()->create([
            'week_num' => 1,
            'year' => 2026,
        ]);

        $account = Account::factory()->create([
            'week_id' => $week->id,
            'year' => '2026',
            'user_gallery' => $gallery->name,
        ]);

        Deduction::factory()->create([
            'accountant_id' => $account->id,
            'amount' => 50,
            'description' => 'Late',
        ]);

        Bonus::factory()->create([
            'accountant_id' => $account->id,
            'amount' => 100,
            'description' => 'Performance',
        ]);

        $manager = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 2,
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->getJson("/api/accounts/{$account->id}/details");

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'deduction')
            ->assertJsonPath('data.0.amount', 50)
            ->assertJsonPath('data.1.type', 'bonus')
            ->assertJsonPath('data.1.amount', 100);
    }

    public function test_manager_can_update_received_status(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Aleppo',
            'address' => 'Main',
        ]);

        $account = Account::factory()->create([
            'received' => '0',
            'user_gallery' => $gallery->name,
        ]);

        $manager = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 2,
        ]);

        $this->actingAsSanctum($manager);

        $response = $this->putJson("/api/accounts/{$account->id}/received", [
            'received' => '1',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('accountants', [
            'id' => $account->id,
            'received' => '1',
        ]);
    }

    public function test_manager_can_view_and_update_account(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Damascus',
            'address' => 'Center',
        ]);

        $week = Week::factory()->create([
            'week_num' => 2,
            'year' => 2026,
        ]);

        $employee = User::factory()->create([
            'gallery_id' => $gallery->id,
            'salary' => 1000,
        ]);

        $account = Account::query()->create([
            'user_id' => $employee->id,
            'user_name' => $employee->user_name,
            'user_position' => '4',
            'user_gallery' => $gallery->name,
            'sales_count' => 0,
            'sales_amount' => 0,
            'deduction_amount' => 0,
            'working_days_count' => 0,
            'salary' => 1000,
            'week_id' => $week->id,
            'year' => '2026',
            'total_amount' => 1000,
            'received' => '0',
        ]);

        $manager = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 2,
        ]);

        $this->actingAsSanctum($manager);

        $showResponse = $this->getJson("/api/accounts/{$account->id}");

        $showResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $account->id)
            ->assertJsonPath('data.user_name', $employee->user_name)
            ->assertJsonPath('data.user_gallery', $gallery->name)
            ->assertJsonPath('data.salary', 1000)
            ->assertJsonPath('data.total_amount', 1000);

        Deduction::factory()->create([
            'accountant_id' => $account->id,
            'amount' => 75,
            'description' => 'Late arrival',
        ]);

        $updateResponse = $this->putJson("/api/accounts/{$account->id}", [
            'deduction_amount' => 75,
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $account->id)
            ->assertJsonPath('data.deduction_amount', 75)
            ->assertJsonPath('data.total_amount', 925);

        $this->assertDatabaseHas('accountants', [
            'id' => $account->id,
            'deduction_amount' => 75,
            'total_amount' => 925,
        ]);
    }

    public function test_sales_user_cannot_access_weekly_accounts(): void
    {
        $gallery = Gallery::query()->create([
            'name' => 'Aleppo',
            'address' => 'Main',
        ]);

        $salesUser = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 4,
        ]);

        $this->actingAsSanctum($salesUser);

        $response = $this->getJson('/api/accounts/weekly?week=1&year=2026&gallery_id='.$gallery->id);

        $response
            ->assertStatus(403)
            ->assertJsonPath('data', 'your computer harmly damaged');
    }
}
