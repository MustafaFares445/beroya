<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bonus;
use App\Models\Deduction;
use App\Models\Gallery;
use App\Models\User;
use App\Models\Week;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountAdjustmentService
{
    public function __construct(private readonly AccountService $accountService)
    {
    }

    /**
     * @return Collection<int, Account>
     */
    public function listWeeklyAccounts(int $weekNumber, string $year, int $galleryId, User $legacyUser): Collection
    {
        $week = Week::query()
            ->where('week_num', $weekNumber)
            ->where('year', $year)
            ->first();

        if ($week === null) {
            return collect();
        }

        $query = Account::query()
            ->where('week_id', $week->id)
            ->where('year', $year);

        if ($galleryId === 0 && (int) $legacyUser->permetions_level === 1) {
            $accounts = $query->get();
        } else {
            $gallery = Gallery::query()->find($galleryId);

            if ($gallery === null) {
                return collect();
            }

            $accounts = $query
                ->where('user_gallery', $gallery->name)
                ->get();
        }

        return $accounts->map(function (Account $account): Account {
            $user = User::query()->find($account->user_id);
            $account->setAttribute('salary', $user?->salary);

            return $account;
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function accountDetails(Account $account): Collection
    {
        $deductions = Deduction::query()
            ->where('accountant_id', $account->id)
            ->get()
            ->map(static fn (Deduction $deduction): array => [
                'id' => $deduction->id,
                'amount' => $deduction->amount,
                'description' => $deduction->description,
                'accountant_id' => $deduction->accountant_id,
                'type' => 'deduction',
            ]);

        $bonuses = Bonus::query()
            ->where('accountant_id', $account->id)
            ->get()
            ->map(static fn (Bonus $bonus): array => [
                'id' => $bonus->id,
                'amount' => $bonus->amount,
                'description' => $bonus->description,
                'accountant_id' => $bonus->accountant_id,
                'type' => 'bonus',
            ]);

        return $deductions->merge($bonuses)->values();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateAccount(Account $account, array $payload): bool
    {
        $updated = $account->update([
            'deduction_amount' => (int) $payload['deduction_amount'],
        ]);

        if ($updated) {
            $this->accountService->recalculateAccountTotal(
                (int) $account->user_id,
                (int) $account->week_id,
                (int) $account->year,
            );
        }

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateReceived(Account $account, array $payload): bool
    {
        $received = ($payload['received'] ?? '0') === '1' ? '1' : '0';

        return $account->update(['received' => $received]);
    }

    public function findAccountByLegacyIdentifiers(?int $accountId, ?int $userId, ?int $weekId): ?Account
    {
        if ($accountId !== null) {
            return Account::query()->find($accountId);
        }

        if ($userId !== null && $weekId !== null) {
            return Account::query()
                ->where('user_id', $userId)
                ->where('week_id', $weekId)
                ->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function storeBonus(Account $account, array $payload): Bonus
    {
        return DB::transaction(function () use ($account, $payload): Bonus {
            $bonus = Bonus::query()->create([
                'accountant_id' => $account->id,
                'amount' => (int) $payload['amount'],
                'description' => (string) $payload['description'],
            ]);

            $this->recalculateForAccount($account);

            return $bonus;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateBonus(Bonus $bonus, array $payload): Bonus
    {
        return DB::transaction(function () use ($bonus, $payload): Bonus {
            $bonus->update([
                'amount' => (int) $payload['amount'],
                'description' => (string) $payload['description'],
            ]);

            $account = $bonus->account;

            if ($account !== null) {
                $this->recalculateForAccount($account);
            }

            return $bonus->fresh();
        });
    }

    public function deleteBonus(Bonus $bonus): void
    {
        DB::transaction(function () use ($bonus): void {
            $account = $bonus->account;
            $bonus->delete();

            if ($account !== null) {
                $this->recalculateForAccount($account);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function storeDeduction(Account $account, array $payload): Deduction
    {
        return DB::transaction(function () use ($account, $payload): Deduction {
            $deduction = Deduction::query()->create([
                'accountant_id' => $account->id,
                'amount' => (int) $payload['amount'],
                'description' => (string) $payload['description'],
            ]);

            $this->recalculateForAccount($account);

            return $deduction;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateDeduction(Deduction $deduction, array $payload): Deduction
    {
        return DB::transaction(function () use ($deduction, $payload): Deduction {
            $deduction->update([
                'amount' => (int) $payload['amount'],
                'description' => (string) $payload['description'],
            ]);

            $account = $deduction->account;

            if ($account !== null) {
                $this->recalculateForAccount($account);
            }

            return $deduction->fresh();
        });
    }

    public function deleteDeduction(Deduction $deduction): void
    {
        DB::transaction(function () use ($deduction): void {
            $account = $deduction->account;
            $deduction->delete();

            if ($account !== null) {
                $this->recalculateForAccount($account);
            }
        });
    }

    private function recalculateForAccount(Account $account): void
    {
        $this->accountService->recalculateAccountTotal(
            (int) $account->user_id,
            (int) $account->week_id,
            (int) $account->year,
        );
    }
}
