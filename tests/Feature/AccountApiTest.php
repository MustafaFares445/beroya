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
            ->assertJsonCount(2, 'data');
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
