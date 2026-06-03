<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bonus;
use App\Models\Deduction;
use App\Models\Sale;
use App\Models\User;
use App\Models\Week;
use Illuminate\Support\Carbon;

class AccountService
{
    public function ensureWeekExists(int $weekNumber, int $year): Week
    {
        $existingWeek = Week::query()
            ->where('week_num', $weekNumber)
            ->where('year', $year)
            ->first();

        if ($existingWeek !== null) {
            return $existingWeek;
        }

        $firstDay = Carbon::create($year, 1, 1, 0, 0, 0);
        $daysUntilFriday = (5 - $firstDay->dayOfWeek + 7) % 7;
        $firstFriday = $firstDay->copy()->addDays($daysUntilFriday);

        $weekStart = $firstFriday->copy()->addDays(($weekNumber - 1) * 7);
        $weekEnd = $weekStart->copy()->addDays(6);

        return Week::query()->create([
            'week_num' => $weekNumber,
            'year' => $year,
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekEnd->toDateString(),
        ]);
    }

    public function ensureAccountsForWeekExists(Week $week): void
    {
        $users = User::query()
            ->with('gallery')
            ->whereNotIn('permetions_level', [1, 111])
            ->get();

        foreach ($users as $user) {
            $account = Account::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'week_id' => $week->id,
                ],
                [
                    'user_name' => (string) $user->user_name,
                    'user_position' => (string) $user->permetions_level,
                    'user_gallery' => (string) ($user->gallery?->name ?? ''),
                    'sales_count' => 0,
                    'sales_amount' => 0,
                    'deduction_amount' => 0,
                    'working_days_count' => 0,
                    'salary' => (int) $user->salary,
                    'year' => (string) $week->year,
                    'total_amount' => 0,
                    'received' => '0',
                ],
            );

            if ($account->wasRecentlyCreated) {
                $this->recalculateAccountTotal($user->id, $week->id, $week->year);
            }
        }
    }

    public function recalculateUserAccounts(int $userId): void
    {
        $accounts = Account::query()
            ->where('user_id', $userId)
            ->get(['week_id', 'year']);

        foreach ($accounts as $account) {
            $this->recalculateAccountTotal($userId, (int) $account->week_id, (int) $account->year);
        }
    }

    public function findOrCreateWeekForDate(string $date): Week
    {
        $existingWeek = Week::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($existingWeek !== null) {
            return $existingWeek;
        }

        $dateCarbon = Carbon::parse($date);
        $year = $dateCarbon->year;

        for ($weekNumber = 1; $weekNumber <= 53; $weekNumber++) {
            $week = $this->ensureWeekExists($weekNumber, $year);

            if ($dateCarbon->betweenIncluded($week->start_date, $week->end_date)) {
                return $week;
            }
        }

        return $this->ensureWeekExists(1, $year);
    }

    public function ensureAccountExistsForUser(User $user, Week $week): Account
    {
        return Account::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'week_id' => $week->id,
            ],
            [
                'user_name' => (string) $user->user_name,
                'user_position' => (string) $user->permetions_level,
                'user_gallery' => (string) ($user->gallery?->name ?? ''),
                'sales_count' => 0,
                'sales_amount' => 0,
                'deduction_amount' => 0,
                'working_days_count' => 0,
                'salary' => (int) $user->salary,
                'year' => (string) $week->year,
                'total_amount' => 0,
                'received' => '0',
            ],
        );
    }

    public function recalculateAccountTotal(int $userId, int $weekId, int $year): bool
    {
        $user = User::query()->find($userId);
        if ($user === null) {
            return false;
        }

        $salary = (float) $user->salary;

        $salesCount = Sale::query()
            ->where('user_id', $userId)
            ->where('status', 'done')
            ->where('week_id', $weekId)
            ->count();

        $salesAmount = (float) Sale::query()
            ->where('user_id', $userId)
            ->where('status', 'done')
            ->where('week_id', $weekId)
            ->sum('user_comiss');

        $totalDeductions = (float) Deduction::query()
            ->whereHas('account', static function ($query) use ($userId, $weekId, $year): void {
                $query
                    ->where('user_id', $userId)
                    ->where('week_id', $weekId)
                    ->where('year', (string) $year);
            })
            ->sum('amount');

        $totalBonuses = (float) Bonus::query()
            ->whereHas('account', static function ($query) use ($userId, $weekId, $year): void {
                $query
                    ->where('user_id', $userId)
                    ->where('week_id', $weekId)
                    ->where('year', (string) $year);
            })
            ->sum('amount');

        $totalAmount = (int) round($salary + $salesAmount + $totalBonuses - $totalDeductions);

        Account::query()
            ->where('user_id', $userId)
            ->where('week_id', $weekId)
            ->where('year', (string) $year)
            ->update([
                'sales_count' => $salesCount,
                'sales_amount' => (int) round($salesAmount),
                'deduction_amount' => (int) round($totalDeductions),
                'total_amount' => $totalAmount,
            ]);

        return true;
    }
}
