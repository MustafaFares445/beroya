<?php

namespace App\Services;

use App\Models\Province;
use Illuminate\Support\Facades\DB;

class ProvinceService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): Province
    {
        return DB::transaction(static function () use ($payload): Province {
            return Province::query()->create([
                'name' => (string) $payload['name'],
                'is_active' => (bool) $payload['is_active'],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Province $province, array $payload): Province
    {
        return DB::transaction(function () use ($province, $payload): Province {
            $province->update([
                'name' => (string) $payload['name'],
                'is_active' => (bool) $payload['is_active'],
            ]);

            return $province->fresh() ?? $province;
        });
    }

    public function delete(Province $province): void
    {
        $province->delete();
    }
}
