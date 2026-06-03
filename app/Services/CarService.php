<?php

namespace App\Services;

use App\Models\Car;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CarService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): Car
    {
        return DB::transaction(function () use ($payload): Car {
            $data = [
                'market_id' => (int) $payload['market_id'],
                'model_id' => (int) $payload['model_id'],
                'year' => (string) $payload['year'],
                'gasoline' => (string) $payload['gasoline'],
                'engine' => (string) $payload['engine'],
                'transmission' => (string) $payload['transmission'],
                'color' => (string) $payload['color'],
                'distance' => (string) $payload['distance'],
                'imported' => (string) $payload['imported'],
                'spray' => (string) $payload['spray'],
                'status' => (string) $payload['status'],
                'description' => (string) $payload['description'],
                'plateNumber' => (string) $payload['plateNumber'],
                'notes' => (string) $payload['notes'],
                'price' => (int) $payload['price'],
                'possession' => (string) $payload['possession'],
                'owner_name' => (string) $payload['owner_name'],
                'owner_phone' => (string) $payload['owner_phone'],
                'gallery_id' => (int) $payload['gallery_id'],
                'car_sale_state' => 1,
            ];

            for ($slot = 1; $slot <= 6; $slot++) {
                $file = $payload["image_{$slot}"] ?? null;
                $data["image_{$slot}"] = $file instanceof UploadedFile ? $this->saveImage($file) : '';
            }

            return Car::query()->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Car $car, array $payload): Car
    {
        return DB::transaction(function () use ($car, $payload): Car {
            $data = [
                'market_id' => (int) $payload['market_id'],
                'model_id' => (int) $payload['model_id'],
                'year' => (string) $payload['year'],
                'gasoline' => (string) $payload['gasoline'],
                'engine' => (string) $payload['engine'],
                'transmission' => (string) $payload['transmission'],
                'color' => (string) $payload['color'],
                'distance' => (string) $payload['distance'],
                'imported' => (string) $payload['imported'],
                'spray' => (string) $payload['spray'],
                'status' => (string) $payload['status'],
                'description' => (string) $payload['description'],
                'plateNumber' => (string) $payload['plateNumber'],
                'notes' => (string) $payload['notes'],
                'price' => (int) $payload['price'],
                'possession' => (string) $payload['possession'],
                'owner_name' => (string) $payload['owner_name'],
                'owner_phone' => (string) $payload['owner_phone'],
                'gallery_id' => (int) $payload['gallery_id'],
            ];

            for ($slot = 1; $slot <= 6; $slot++) {
                $field = "image_{$slot}";
                $oldImageName = (string) ($car->{$field} ?? '');
                $uploadedFile = $payload[$field] ?? null;
                $deleteFlag = $this->toBoolean($payload["delete_image{$slot}"] ?? false);

                if ($uploadedFile instanceof UploadedFile) {
                    $data[$field] = $this->saveImage($uploadedFile);
                    $this->deleteImage($oldImageName);
                    continue;
                }

                if ($deleteFlag) {
                    $data[$field] = '';
                    $this->deleteImage($oldImageName);
                }
            }

            $car->update($data);

            return $car->fresh();
        });
    }

    public function updateSaleState(Car $car, int $saleState): Car
    {
        $car->update([
            'car_sale_state' => $saleState,
        ]);

        return $car->fresh();
    }

    public function delete(Car $car): void
    {
        for ($slot = 1; $slot <= 6; $slot++) {
            $field = "image_{$slot}";
            $imageName = (string) ($car->{$field} ?? '');
            $this->deleteImage($imageName);
        }

        $car->delete();
    }

    private function saveImage(UploadedFile $file): string
    {
        $directory = $this->carsUploadDirectory();

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $fileName = bin2hex(random_bytes(16)).'.'.$extension;

        $file->move($directory, $fileName);

        return $fileName;
    }

    private function deleteImage(string $imageName): void
    {
        if ($imageName === '' || $imageName === 'logo.png') {
            return;
        }

        $imagePath = $this->carsUploadDirectory().DIRECTORY_SEPARATOR.$imageName;
        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }
    }

    private function carsUploadDirectory(): string
    {
        $directory = public_path('data/uploads/cars');
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return false;
    }
}
