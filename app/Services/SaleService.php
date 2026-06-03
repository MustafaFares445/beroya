<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Sale;
use App\Models\User;
use App\Models\Week;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SaleService
{
    public function __construct(private readonly AccountService $accountService)
    {
    }

    /**
     * @return Collection<int, Sale>
     */
    public function list(string $status, int $galleryId, User $legacyUser): Collection
    {
        $query = Sale::query()->where('status', $status);

        if ($status === 'done') {
            return $query->get();
        }

        if ((int) $legacyUser->gallery_id === 0) {
            return $query->get();
        }

        return $query
            ->whereHas('user', static function ($userQuery) use ($galleryId): void {
                $userQuery->where('gallery_id', $galleryId);
            })
            ->get();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): Sale
    {
        return DB::transaction(function () use ($payload): Sale {
            $date = (string) $payload['date'];
            $week = $this->accountService->findOrCreateWeekForDate($date);
            $buyerComiss = (int) ($payload['buyer_comiss'] ?? 0);
            $status = $buyerComiss !== 0 ? 'done' : 'hold';
            $approved = $buyerComiss !== 0 ? '1' : '0';

            $sale = Sale::query()->create([
                'car_id' => (int) $payload['car_id'],
                'car_brand' => (string) ($payload['car_brand'] ?? ''),
                'car_model' => (string) ($payload['car_model'] ?? ''),
                'car_number' => (string) ($payload['car_number'] ?? ''),
                'car_name' => (string) $payload['car_name'],
                'price' => (int) $payload['price'],
                'employee_name' => (string) ($payload['employee_name'] ?? ''),
                'user_comiss' => (int) ($payload['user_comiss'] ?? 0),
                'user_note' => (string) ($payload['user_note'] ?? ''),
                'owner_name' => (string) ($payload['owner_name'] ?? ''),
                'owner_phone' => (string) ($payload['owner_phone'] ?? ''),
                'owner_comiss' => (int) ($payload['owner_comiss'] ?? 0),
                'owner_comiss_payed' => (int) ($payload['owner_comiss_payed'] ?? 0),
                'buyer_name' => (string) ($payload['buyer_name'] ?? ''),
                'buyer_phone' => (int) ($payload['buyer_phone'] ?? 0),
                'buyer_comiss' => $buyerComiss,
                'buyer_comiss_payed' => (int) ($payload['buyer_comiss_payed'] ?? 0),
                'owner_id_image' => $this->resolveUploadedImage($payload['owner_id_image'] ?? null),
                'buyer_id_image' => $this->resolveUploadedImage($payload['buyer_id_image'] ?? null),
                'contract_image' => $this->resolveUploadedImage($payload['contract_image'] ?? null),
                'date' => $date,
                'week_id' => $week->id,
                'user_id' => (int) $payload['user_id'],
                'status' => $status,
                'approved' => $approved,
            ]);

            if ($status === 'done') {
                $this->recalculateForSale($sale);
            }

            return $sale->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Sale $sale, array $payload): Sale
    {
        return DB::transaction(function () use ($sale, $payload): Sale {
            $previousStatus = (string) $sale->status;
            $previousUserId = (int) $sale->user_id;
            $previousWeekId = (int) $sale->week_id;
            $previousYear = (int) $sale->date->format('Y');

            $date = (string) $payload['date'];
            $week = $this->accountService->findOrCreateWeekForDate($date);
            $buyerComiss = (int) ($payload['buyer_comiss'] ?? 0);
            $status = $buyerComiss !== 0 ? 'done' : 'hold';

            $sale->update([
                'car_id' => (int) $payload['car_id'],
                'car_brand' => (string) ($payload['car_brand'] ?? ''),
                'car_model' => (string) ($payload['car_model'] ?? ''),
                'car_number' => (string) ($payload['car_number'] ?? ''),
                'car_name' => (string) $payload['car_name'],
                'price' => (int) $payload['price'],
                'employee_name' => (string) ($payload['employee_name'] ?? ''),
                'user_comiss' => (int) ($payload['user_comiss'] ?? 0),
                'user_note' => (string) ($payload['user_note'] ?? ''),
                'owner_name' => (string) ($payload['owner_name'] ?? ''),
                'owner_phone' => (string) ($payload['owner_phone'] ?? ''),
                'owner_comiss' => (int) ($payload['owner_comiss'] ?? 0),
                'owner_comiss_payed' => (int) ($payload['owner_comiss_payed'] ?? 0),
                'buyer_name' => (string) ($payload['buyer_name'] ?? ''),
                'buyer_phone' => (int) ($payload['buyer_phone'] ?? 0),
                'buyer_comiss' => $buyerComiss,
                'buyer_comiss_payed' => (int) ($payload['buyer_comiss_payed'] ?? 0),
                'owner_id_image' => $this->resolveUpdatedImage($payload['owner_id_image'] ?? null, (string) $sale->owner_id_image),
                'buyer_id_image' => $this->resolveUpdatedImage($payload['buyer_id_image'] ?? null, (string) $sale->buyer_id_image),
                'contract_image' => $this->resolveUpdatedImage($payload['contract_image'] ?? null, (string) $sale->contract_image),
                'date' => $date,
                'week_id' => $week->id,
                'user_id' => (int) $payload['user_id'],
                'status' => $status,
            ]);

            $updatedSale = $sale->fresh();

            if ($previousStatus === 'done') {
                $this->accountService->recalculateAccountTotal($previousUserId, $previousWeekId, $previousYear);
            }

            if ($status === 'done') {
                $this->recalculateForSale($updatedSale);
            }

            return $updatedSale;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function complete(Sale $sale, array $payload): Sale
    {
        return DB::transaction(function () use ($sale, $payload): Sale {
            $sale->update([
                'user_comiss' => (int) ($payload['user_comiss'] ?? 0),
                'owner_comiss' => (int) ($payload['owner_comiss'] ?? 0),
                'owner_comiss_payed' => (int) ($payload['owner_comiss_payed'] ?? 0),
                'buyer_comiss' => (int) ($payload['buyer_comiss'] ?? 0),
                'buyer_comiss_payed' => (int) ($payload['buyer_comiss_payed'] ?? 0),
                'employee_name' => (string) ($payload['employee_name'] ?? ''),
                'user_note' => (string) ($payload['user_note'] ?? ''),
                'status' => 'done',
            ]);

            $updatedSale = $sale->fresh();
            $user = User::query()->with('gallery')->find($updatedSale->user_id);

            if ($user !== null) {
                $week = Week::query()->find($updatedSale->week_id);

                if ($week !== null) {
                    $this->accountService->ensureAccountExistsForUser($user, $week);
                }
            }

            if ($updatedSale->car_id) {
                Car::query()
                    ->whereKey($updatedSale->car_id)
                    ->update(['car_sale_state' => 3]);
            }

            $this->recalculateForSale($updatedSale);

            return $updatedSale;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function approveOrder(Sale $sale, array $payload): Sale
    {
        $sale->update([
            'approved' => '1',
            'owner_name' => (string) $payload['ownerName'],
            'owner_phone' => (string) $payload['ownerPhone'],
        ]);

        return $sale->fresh();
    }

    public function deleteOrder(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale): Sale {
            $wasDone = (string) $sale->status === 'done';
            $userId = (int) $sale->user_id;
            $weekId = (int) $sale->week_id;
            $year = (int) $sale->date->format('Y');

            $this->deleteImage((string) $sale->owner_id_image);
            $this->deleteImage((string) $sale->buyer_id_image);
            $this->deleteImage((string) $sale->contract_image);

            $deletedSale = $sale->replicate();
            $deletedSale->id = $sale->id;

            $sale->delete();

            if ($wasDone) {
                $this->accountService->recalculateAccountTotal($userId, $weekId, $year);
            }

            return $deletedSale;
        });
    }

    private function recalculateForSale(Sale $sale): void
    {
        $this->accountService->recalculateAccountTotal(
            (int) $sale->user_id,
            (int) $sale->week_id,
            (int) $sale->date->format('Y'),
        );
    }

    private function resolveUploadedImage(mixed $file): string
    {
        if ($file instanceof UploadedFile) {
            return $this->saveImage($file);
        }

        return '';
    }

    private function resolveUpdatedImage(mixed $file, string $currentImage): string
    {
        if ($file instanceof UploadedFile) {
            return $this->saveImage($file);
        }

        return $currentImage;
    }

    private function saveImage(UploadedFile $file): string
    {
        $directory = $this->salesUploadDirectory();
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

        $imagePath = $this->salesUploadDirectory().DIRECTORY_SEPARATOR.$imageName;

        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }
    }

    private function salesUploadDirectory(): string
    {
        $directory = public_path('data/uploads/sales');
        File::ensureDirectoryExists($directory);

        return $directory;
    }
}
