<?php

namespace App\Services;

use App\Models\Car;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CarService
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload, ?User $actor = null, ?string $ipAddress = null): Car
    {
        return DB::transaction(function () use ($payload, $actor, $ipAddress): Car {
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

            $car = Car::query()->create($data);

            $this->activityLogger->record(
                $actor,
                'car.created',
                $car,
                [],
                $this->carActivityValues($car),
                $ipAddress,
            );

            return $car;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Car $car, array $payload, ?User $actor = null, ?string $ipAddress = null): Car
    {
        return DB::transaction(function () use ($car, $payload, $actor, $ipAddress): Car {
            $oldValues = $this->carActivityValues($car);

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

            $updatedCar = $car->fresh();

            $this->activityLogger->record(
                $actor,
                'car.updated',
                $updatedCar,
                $oldValues,
                $this->carActivityValues($updatedCar),
                $ipAddress,
            );

            return $updatedCar;
        });
    }

    public function updateSaleState(Car $car, int $saleState, ?User $actor = null, ?string $ipAddress = null): Car
    {
        $oldValues = [
            'car_sale_state' => (int) $car->car_sale_state,
        ];

        $car->update([
            'car_sale_state' => $saleState,
        ]);

        $updatedCar = $car->fresh();

        $this->activityLogger->record(
            $actor,
            'car.sale_state_updated',
            $updatedCar,
            $oldValues,
            [
                'car_sale_state' => $saleState,
            ],
            $ipAddress,
        );

        return $updatedCar;
    }

    public function delete(Car $car, ?User $actor = null, ?string $ipAddress = null): void
    {
        DB::transaction(function () use ($car, $actor, $ipAddress): void {
            $oldValues = $this->carActivityValues($car);

            for ($slot = 1; $slot <= 6; $slot++) {
                $field = "image_{$slot}";
                $imageName = (string) ($car->{$field} ?? '');
                $this->deleteImage($imageName);
            }

            $car->delete();

            $this->activityLogger->record(
                $actor,
                'car.deleted',
                $car,
                $oldValues,
                [],
                $ipAddress,
            );
        });
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

    /**
     * @return array<string, mixed>
     */
    private function carActivityValues(Car $car): array
    {
        return $car->only([
            'market_id',
            'model_id',
            'year',
            'gasoline',
            'engine',
            'transmission',
            'color',
            'distance',
            'imported',
            'spray',
            'status',
            'description',
            'plateNumber',
            'notes',
            'price',
            'possession',
            'owner_name',
            'owner_phone',
            'gallery_id',
            'image_1',
            'image_2',
            'image_3',
            'image_4',
            'image_5',
            'image_6',
            'car_sale_state',
        ]);
    }
}
