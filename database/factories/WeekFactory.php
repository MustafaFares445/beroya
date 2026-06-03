<?php

namespace Database\Factories;

use App\Models\Week;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Week>
 */
class WeekFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) now()->format('Y');
        $firstDay = Carbon::create($year, 1, 1, 0, 0, 0);
        $daysUntilFriday = (5 - $firstDay->dayOfWeek + 7) % 7;
        $startDate = $firstDay->copy()->addDays($daysUntilFriday);

        return [
            'week_num' => 1,
            'year' => $year,
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addDays(6)->toDateString(),
        ];
    }
}
