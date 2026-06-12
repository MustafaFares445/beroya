<?php

namespace App\Services;

use App\Models\RealEstateOfficePhone;
use Illuminate\Support\Facades\DB;

class RealEstateOfficePhoneService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): RealEstateOfficePhone
    {
        return DB::transaction(function () use ($payload): RealEstateOfficePhone {
            return RealEstateOfficePhone::query()->create([
                'real_estate_office_id' => (int) $payload['real_estate_office_id'],
                'phone' => (string) $payload['phone'],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(RealEstateOfficePhone $realEstateOfficePhone, array $payload): RealEstateOfficePhone
    {
        return DB::transaction(function () use ($realEstateOfficePhone, $payload): RealEstateOfficePhone {
            $realEstateOfficePhone->update([
                'real_estate_office_id' => (int) $payload['real_estate_office_id'],
                'phone' => (string) $payload['phone'],
            ]);

            return $realEstateOfficePhone->fresh() ?? $realEstateOfficePhone;
        });
    }

    public function delete(RealEstateOfficePhone $realEstateOfficePhone): void
    {
        $realEstateOfficePhone->delete();
    }
}
