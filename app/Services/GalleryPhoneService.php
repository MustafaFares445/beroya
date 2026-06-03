<?php

namespace App\Services;

use App\Models\GalleryPhone;
use Illuminate\Support\Facades\DB;

class GalleryPhoneService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): GalleryPhone
    {
        return DB::transaction(static function () use ($payload): GalleryPhone {
            return GalleryPhone::query()->create([
                'phone' => (string) $payload['phone'],
                'gallery_id' => (int) $payload['gallery_id'],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(GalleryPhone $galleryPhone, array $payload): GalleryPhone
    {
        return DB::transaction(static function () use ($galleryPhone, $payload): GalleryPhone {
            $data = [
                'phone' => (string) $payload['phone'],
            ];

            if (isset($payload['gallery_id']) && $payload['gallery_id'] !== null && $payload['gallery_id'] !== '') {
                $data['gallery_id'] = (int) $payload['gallery_id'];
            }

            $galleryPhone->update($data);

            return $galleryPhone->fresh();
        });
    }

    public function delete(GalleryPhone $galleryPhone): void
    {
        $galleryPhone->delete();
    }
}
