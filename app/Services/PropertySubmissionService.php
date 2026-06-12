<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertySubmission;
use App\Models\RealEstateOffice;
use App\Models\User;
use App\Support\RealEstate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PropertySubmissionService
{
    /**
     * @return Collection<int, PropertySubmission>
     */
    public function list(): Collection
    {
        return PropertySubmission::query()->with([
            'province',
            'office.province',
            'mainCategory',
            'subcategory',
            'publishedProperty',
        ])->orderByDesc('id')->get();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): PropertySubmission
    {
        return DB::transaction(function () use ($payload): PropertySubmission {
            $office = $this->resolveOfficeFromPayload($payload);

            $submission = PropertySubmission::query()->create([
                'offer_number' => (string) $payload['offer_number'],
                'province_id' => $office?->province_id ?? (int) $payload['province_id'],
                'office_id' => $office?->id,
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
                'submission_note' => $this->nullableString($payload, 'submission_note'),
                'status' => 'pending',
                'reject_reason' => null,
                'published_property_id' => null,
                'reviewed_at' => null,
            ]);

            return $submission->fresh([
                'province',
                'office.province',
                'mainCategory',
                'subcategory',
                'publishedProperty',
            ]) ?? $submission;
        });
    }

    public function approve(PropertySubmission $submission, ?User $reviewer = null): PropertySubmission
    {
        return DB::transaction(function () use ($submission, $reviewer): PropertySubmission {
            $submission->loadMissing([
                'province',
                'office.province',
                'mainCategory',
                'subcategory',
                'publishedProperty',
            ]);

            $office = $this->resolveApprovalOffice($submission, $reviewer);

            if ($submission->published_property_id === null) {
                $property = Property::query()->create([
                    'offer_number' => (string) $submission->offer_number,
                    'province_id' => (int) $office->province_id,
                    'office_id' => (int) $office->id,
                    'main_category_id' => (int) $submission->main_category_id,
                    'subcategory_id' => (int) $submission->subcategory_id,
                    'property_nature' => (string) $submission->property_nature,
                    'title_type' => (string) $submission->title_type,
                    'area' => (string) $submission->area,
                    'district' => (string) $submission->district,
                    'address' => (string) $submission->address,
                    'building' => (string) $submission->building,
                    'floor' => (string) $submission->floor,
                    'direction' => (string) $submission->direction,
                    'rooms_count' => (int) $submission->rooms_count,
                    'area_size' => (int) $submission->area_size,
                    'price' => (int) $submission->price,
                    'ownership_type' => (string) $submission->ownership_type,
                    'offer_type' => (string) $submission->offer_type,
                    'rent_duration' => $submission->rent_duration,
                    'owner_name' => (string) $submission->owner_name,
                    'owner_phone' => (string) $submission->owner_phone,
                    'status' => 'available',
                ]);

                $submission->published_property_id = $property->id;
            }

            $submission->province_id = (int) $office->province_id;
            $submission->office_id = (int) $office->id;
            $submission->status = 'approved';
            $submission->reject_reason = null;
            $submission->reviewed_at = now();
            $submission->save();

            return $submission->fresh([
                'province',
                'office.province',
                'mainCategory',
                'subcategory',
                'publishedProperty',
            ]) ?? $submission;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function reject(PropertySubmission $submission, array $payload): PropertySubmission
    {
        return DB::transaction(function () use ($submission, $payload): PropertySubmission {
            $submission->update([
                'status' => 'rejected',
                'reject_reason' => (string) $payload['reject_reason'],
                'reviewed_at' => now(),
            ]);

            return $submission->fresh([
                'province',
                'office.province',
                'mainCategory',
                'subcategory',
                'publishedProperty',
            ]) ?? $submission;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveOfficeFromPayload(array $payload): ?RealEstateOffice
    {
        $officeId = $this->nullableInteger($payload, 'office_id');
        if ($officeId === null) {
            return null;
        }

        return RealEstateOffice::query()
            ->select(['id', 'province_id'])
            ->findOrFail($officeId);
    }

    private function resolveApprovalOffice(PropertySubmission $submission, ?User $reviewer): RealEstateOffice
    {
        if ($submission->office_id !== null) {
            return RealEstateOffice::query()
                ->select(['id', 'province_id', 'is_active'])
                ->findOrFail((int) $submission->office_id);
        }

        if ($reviewer !== null && $reviewer->real_estate_office_id !== null) {
            return RealEstateOffice::query()
                ->select(['id', 'province_id', 'is_active'])
                ->findOrFail((int) $reviewer->real_estate_office_id);
        }

        return RealEstateOffice::query()
            ->select(['id', 'province_id', 'is_active'])
            ->where('province_id', (int) $submission->province_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function nullableInteger(array $payload, string $key): ?int
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null || $payload[$key] === '') {
            return null;
        }

        return (int) $payload[$key];
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
