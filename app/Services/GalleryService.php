<?php

namespace App\Services;

use App\Models\Gallery;
use Illuminate\Support\Facades\DB;

class GalleryService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): Gallery
    {
        return DB::transaction(static function () use ($payload): Gallery {
            return Gallery::query()->create([
                'name' => (string) $payload['name'],
                'address' => (string) $payload['address'],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Gallery $gallery, array $payload): Gallery
    {
        return DB::transaction(static function () use ($gallery, $payload): Gallery {
            $gallery->update([
                'name' => (string) $payload['name'],
                'address' => (string) $payload['address'],
            ]);

            return $gallery->fresh();
        });
    }

    public function delete(Gallery $gallery): void
    {
        $gallery->delete();
    }
}
