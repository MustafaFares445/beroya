<?php

namespace App\Services;

use App\Models\CarModel;
use Illuminate\Support\Facades\DB;

class CarModelService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): CarModel
    {
        return DB::transaction(static function () use ($payload): CarModel {
            return CarModel::query()->create([
                'name' => (string) $payload['name'],
                'market_id' => (int) $payload['market_id'],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(CarModel $carModel, array $payload): CarModel
    {
        return DB::transaction(static function () use ($carModel, $payload): CarModel {
            $data = [
                'name' => (string) $payload['name'],
            ];

            if (isset($payload['market_id']) && $payload['market_id'] !== null && $payload['market_id'] !== '') {
                $data['market_id'] = (int) $payload['market_id'];
            }

            $carModel->update($data);

            return $carModel->fresh();
        });
    }

    public function delete(CarModel $carModel): void
    {
        $carModel->delete();
    }
}
