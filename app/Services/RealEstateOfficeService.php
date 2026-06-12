<?php

namespace App\Services;

use App\Models\RealEstateOffice;
use Illuminate\Support\Facades\DB;

class RealEstateOfficeService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): RealEstateOffice
    {
        return DB::transaction(function () use ($payload): RealEstateOffice {
            $realEstateOffice = RealEstateOffice::query()->create([
                'province_id' => (int) $payload['province_id'],
                'name' => (string) $payload['name'],
                'address' => (string) $payload['address'],
                'is_active' => (bool) $payload['is_active'],
            ]);

            return $realEstateOffice->fresh('province') ?? $realEstateOffice;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(RealEstateOffice $realEstateOffice, array $payload): RealEstateOffice
    {
        return DB::transaction(function () use ($realEstateOffice, $payload): RealEstateOffice {
            $realEstateOffice->update([
                'province_id' => (int) $payload['province_id'],
                'name' => (string) $payload['name'],
                'address' => (string) $payload['address'],
                'is_active' => (bool) $payload['is_active'],
            ]);

            return $realEstateOffice->fresh('province') ?? $realEstateOffice;
        });
    }

    public function delete(RealEstateOffice $realEstateOffice): void
    {
        $realEstateOffice->delete();
    }
}
