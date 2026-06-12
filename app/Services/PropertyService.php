<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\RealEstateOffice;
use App\Support\RealEstate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PropertyService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Property>
     */
    public function list(array $filters): Collection
    {
        $query = Property::query()->with([
            'province',
            'office.province',
            'mainCategory',
            'subcategory',
            'images',
        ])->orderByDesc('id');

        if (isset($filters['province_id']) && $filters['province_id'] !== null && $filters['province_id'] !== '') {
            $query->where('province_id', (int) $filters['province_id']);
        }

        if (isset($filters['office_id']) && $filters['office_id'] !== null && $filters['office_id'] !== '') {
            $query->where('office_id', (int) $filters['office_id']);
        }

        if (isset($filters['main_category_id']) && $filters['main_category_id'] !== null && $filters['main_category_id'] !== '') {
            $query->where('main_category_id', (int) $filters['main_category_id']);
        }

        if (isset($filters['subcategory_id']) && $filters['subcategory_id'] !== null && $filters['subcategory_id'] !== '') {
            $query->where('subcategory_id', (int) $filters['subcategory_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', (string) $filters['status']);
        }

        if (isset($filters['title_type']) && $filters['title_type'] !== null && $filters['title_type'] !== '') {
            $query->where('title_type', (string) $filters['title_type']);
        }

        if (isset($filters['offer_type']) && $filters['offer_type'] !== null && $filters['offer_type'] !== '') {
            $query->where('offer_type', (string) $filters['offer_type']);
        }

        if (isset($filters['ownership_type']) && $filters['ownership_type'] !== null && $filters['ownership_type'] !== '') {
            $query->where('ownership_type', (string) $filters['ownership_type']);
        }

        if (isset($filters['property_nature']) && $filters['property_nature'] !== null && $filters['property_nature'] !== '') {
            $propertyNature = RealEstate::normalizePropertyNature((string) $filters['property_nature']);

            if ($propertyNature !== null && $propertyNature !== '') {
                $query->where('property_nature', $propertyNature);
            }
        }

        if (isset($filters['rent_duration']) && $filters['rent_duration'] !== null && $filters['rent_duration'] !== '') {
            $query->where('rent_duration', (string) $filters['rent_duration']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search): void {
                $searchQuery
                    ->where('offer_number', 'like', '%'.$search.'%')
                    ->orWhere('property_nature', 'like', '%'.$search.'%')
                    ->orWhere('title_type', 'like', '%'.$search.'%')
                    ->orWhere('area', 'like', '%'.$search.'%')
                    ->orWhere('district', 'like', '%'.$search.'%')
                    ->orWhere('address', 'like', '%'.$search.'%')
                    ->orWhere('building', 'like', '%'.$search.'%')
                    ->orWhere('direction', 'like', '%'.$search.'%')
                    ->orWhere('ownership_type', 'like', '%'.$search.'%')
                    ->orWhere('offer_type', 'like', '%'.$search.'%')
                    ->orWhere('rent_duration', 'like', '%'.$search.'%')
                    ->orWhere('owner_name', 'like', '%'.$search.'%')
                    ->orWhere('owner_phone', 'like', '%'.$search.'%')
                    ->orWhere('status', 'like', '%'.$search.'%');
            });
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): Property
    {
        return DB::transaction(function () use ($payload): Property {
            $office = $this->resolveOffice((int) $payload['office_id']);

            $property = Property::query()->create([
                'offer_number' => (string) $payload['offer_number'],
                'province_id' => (int) $office->province_id,
                'office_id' => (int) $payload['office_id'],
                'main_category_id' => (int) $payload['main_category_id'],
                'subcategory_id' => (int) $payload['subcategory_id'],
                'property_nature' => (string) RealEstate::normalizePropertyNature((string) $payload['property_nature']),
                'title_type' => (string) $payload['title_type'],
                'area' => (string) $payload['area'],
                'district' => (string) $payload['district'],
                'address' => (string) $payload['address'],
                'building' => (string) $payload['building'],
                'floor' => (string) $payload['floor'],
                'direction' => (string) $payload['direction'],
                'rooms_count' => (int) $payload['rooms_count'],
                'area_size' => (int) $payload['area_size'],
                'price' => (int) $payload['price'],
                'ownership_type' => (string) $payload['ownership_type'],
                'offer_type' => (string) $payload['offer_type'],
                'rent_duration' => $this->nullableString($payload, 'rent_duration'),
                'owner_name' => (string) $payload['owner_name'],
                'owner_phone' => (string) $payload['owner_phone'],
                'status' => (string) $payload['status'],
            ]);

            return $property->fresh([
                'province',
                'office.province',
                'mainCategory',
                'subcategory',
                'images',
            ]) ?? $property;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Property $property, array $payload): Property
    {
        return DB::transaction(function () use ($property, $payload): Property {
            $office = $this->resolveOffice((int) $payload['office_id']);

            $property->update([
                'offer_number' => (string) $payload['offer_number'],
                'province_id' => (int) $office->province_id,
                'office_id' => (int) $payload['office_id'],
                'main_category_id' => (int) $payload['main_category_id'],
                'subcategory_id' => (int) $payload['subcategory_id'],
                'property_nature' => (string) RealEstate::normalizePropertyNature((string) $payload['property_nature']),
                'title_type' => (string) $payload['title_type'],
                'area' => (string) $payload['area'],
                'district' => (string) $payload['district'],
                'address' => (string) $payload['address'],
                'building' => (string) $payload['building'],
                'floor' => (string) $payload['floor'],
                'direction' => (string) $payload['direction'],
                'rooms_count' => (int) $payload['rooms_count'],
                'area_size' => (int) $payload['area_size'],
                'price' => (int) $payload['price'],
                'ownership_type' => (string) $payload['ownership_type'],
                'offer_type' => (string) $payload['offer_type'],
                'rent_duration' => $this->nullableString($payload, 'rent_duration'),
                'owner_name' => (string) $payload['owner_name'],
                'owner_phone' => (string) $payload['owner_phone'],
                'status' => (string) $payload['status'],
            ]);

            return $property->fresh([
                'province',
                'office.province',
                'mainCategory',
                'subcategory',
                'images',
            ]) ?? $property;
        });
    }

    public function delete(Property $property): void
    {
        DB::transaction(function () use ($property): void {
            $property->loadMissing('images');

            foreach ($property->images as $image) {
                $this->deleteImageFile((string) $image->image);
            }

            $property->images()->delete();
            $property->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function addImages(Property $property, array $payload): Property
    {
        return DB::transaction(function () use ($property, $payload): Property {
            foreach ($this->normalizeImages($payload['images'] ?? []) as $file) {
                $property->images()->create([
                    'image' => $this->saveImage($file),
                ]);
            }

            return $property->fresh([
                'province',
                'office.province',
                'mainCategory',
                'subcategory',
                'images',
            ]) ?? $property;
        });
    }

    public function deleteImage(PropertyImage $image): Property
    {
        return DB::transaction(function () use ($image): Property {
            $property = $image->property()->firstOrFail();
            $this->deleteImageFile((string) $image->image);
            $image->delete();

            return $property->fresh([
                'province',
                'office.province',
                'mainCategory',
                'subcategory',
                'images',
            ]) ?? $property;
        });
    }

    private function resolveOffice(int $officeId): RealEstateOffice
    {
        return RealEstateOffice::query()
            ->select(['id', 'province_id'])
            ->findOrFail($officeId);
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function normalizeImages(mixed $images): array
    {
        if ($images instanceof UploadedFile) {
            return [$images];
        }

        if (! is_array($images)) {
            return [];
        }

        return array_values(array_filter($images, static function (mixed $image): bool {
            return $image instanceof UploadedFile;
        }));
    }

    private function saveImage(UploadedFile $file): string
    {
        $directory = $this->propertiesUploadDirectory();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $fileName = bin2hex(random_bytes(16)).'.'.$extension;

        $file->move($directory, $fileName);

        return $fileName;
    }

    private function deleteImageFile(string $imageName): void
    {
        if ($imageName === '') {
            return;
        }

        $imagePath = $this->propertiesUploadDirectory().DIRECTORY_SEPARATOR.$imageName;
        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }
    }

    private function propertiesUploadDirectory(): string
    {
        $directory = public_path('data/uploads/properties');
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function nullableString(array $payload, string $key): ?string
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null || $payload[$key] === '') {
            return null;
        }

        return (string) $payload[$key];
    }
}
