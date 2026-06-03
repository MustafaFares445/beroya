<?php

namespace App\Services;

use App\Models\Market;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MarketService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): Market
    {
        return DB::transaction(function () use ($payload): Market {
            $image = $payload['image'] ?? null;
            $imageName = '';

            if ($image instanceof UploadedFile) {
                $imageName = $this->saveImage($image);
            }

            return Market::query()->create([
                'name' => (string) $payload['name'],
                'image' => $imageName,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Market $market, array $payload): Market
    {
        return DB::transaction(function () use ($market, $payload): Market {
            $data = [
                'name' => (string) $payload['name'],
            ];

            $image = $payload['image'] ?? null;
            if ($image instanceof UploadedFile) {
                $oldImageName = (string) $market->image;
                $newImageName = $this->saveImage($image);
                $data['image'] = $newImageName;
                $this->deleteImage($oldImageName);
            }

            $market->update($data);

            return $market->fresh();
        });
    }

    public function delete(Market $market): void
    {
        $imageName = (string) $market->image;
        $market->delete();
        $this->deleteImage($imageName);
    }

    private function saveImage(UploadedFile $file): string
    {
        $directory = $this->marketsUploadDirectory();

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

        $imagePath = $this->marketsUploadDirectory().DIRECTORY_SEPARATOR.$imageName;
        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }
    }

    private function marketsUploadDirectory(): string
    {
        $directory = public_path('data/uploads/markets');
        File::ensureDirectoryExists($directory);

        return $directory;
    }
}
