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

class BonusDeductionApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{account: Account, manager: User}
     */
    private function createAccountContext(): array
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
            'salary' => 500,
        ]);

        $account = Account::factory()->create([
            'user_id' => $employee->id,
            'week_id' => $week->id,
            'year' => '2026',
            'user_gallery' => $gallery->name,
            'salary' => 500,
            'total_amount' => 500,
        ]);

        $manager = User::factory()->create([
            'gallery_id' => $gallery->id,
            'permetions_level' => 2,
        ]);

        return [
            'account' => $account,
            'manager' => $manager,
        ];
    }

    public function test_manager_can_add_bonus_and_recalculate_total(): void
    {
        $context = $this->createAccountContext();
        $this->actingAsSanctum($context['manager']);

        $response = $this->postJson("/api/accounts/{$context['account']->id}/bonuses", [
            'amount' => 100,
            'description' => 'Bonus',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('bonus', [
            'accountant_id' => $context['account']->id,
            'amount' => 100,
        ]);

        $this->assertDatabaseHas('accountants', [
            'id' => $context['account']->id,
            'total_amount' => 600,
        ]);
    }

    public function test_manager_can_add_deduction_and_recalculate_total(): void
    {
        $context = $this->createAccountContext();
        $this->actingAsSanctum($context['manager']);

        $response = $this->postJson("/api/accounts/{$context['account']->id}/deductions", [
            'amount' => 50,
            'description' => 'Deduction',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('deduction', [
            'accountant_id' => $context['account']->id,
            'amount' => 50,
        ]);

        $this->assertDatabaseHas('accountants', [
            'id' => $context['account']->id,
            'deduction_amount' => 50,
            'total_amount' => 450,
        ]);
    }

    public function test_manager_can_update_and_delete_bonus(): void
    {
        $context = $this->createAccountContext();
        $this->actingAsSanctum($context['manager']);

        $bonus = Bonus::factory()->create([
            'accountant_id' => $context['account']->id,
            'amount' => 100,
            'description' => 'Initial',
        ]);

        $this->putJson("/api/bonuses/{$bonus->id}", [
            'amount' => 150,
            'description' => 'Updated',
        ])->assertOk();

        $this->assertDatabaseHas('bonus', [
            'id' => $bonus->id,
            'amount' => 150,
        ]);

        $this->deleteJson("/api/bonuses/{$bonus->id}")->assertOk();

        $this->assertDatabaseMissing('bonus', ['id' => $bonus->id]);
    }

    public function test_manager_can_update_and_delete_deduction(): void
    {
        $context = $this->createAccountContext();
        $this->actingAsSanctum($context['manager']);

        $deduction = Deduction::factory()->create([
            'accountant_id' => $context['account']->id,
            'amount' => 50,
            'description' => 'Initial',
        ]);

        $this->putJson("/api/deductions/{$deduction->id}", [
            'amount' => 75,
            'description' => 'Updated',
        ])->assertOk();

        $this->assertDatabaseHas('deduction', [
            'id' => $deduction->id,
            'amount' => 75,
        ]);

        $this->deleteJson("/api/deductions/{$deduction->id}")->assertOk();

        $this->assertDatabaseMissing('deduction', ['id' => $deduction->id]);
    }
}
