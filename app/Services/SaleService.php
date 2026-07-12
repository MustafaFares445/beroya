<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\User;
use App\Models\Week;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SaleService
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly CarService $carService,
        private readonly ActivityLogger $activityLogger,
        private readonly WorkflowNotificationService $workflowNotificationService,
    ) {}

    /**
     * @return Collection<int, Sale>
     */
    public function list(string $status, int $galleryId, ?int $userId, User $legacyUser): Collection
    {
        $query = Sale::query()->where('status', $status);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($status === 'done') {
            $query->orderByDesc('completed_at')->orderByDesc('updated_at');
        } else {
            $query->orderByDesc('requested_at')->orderByDesc('created_at');
        }

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
    public function store(array $payload, ?User $actor = null, ?string $ipAddress = null): Sale
    {
        return DB::transaction(function () use ($payload, $actor, $ipAddress): Sale {
            $date = (string) $payload['date'];
            $week = $this->accountService->findOrCreateWeekForDate($date);
            $buyerComiss = (int) ($payload['buyer_comiss'] ?? 0);
            $status = $buyerComiss !== 0 ? 'done' : 'hold';
            $approved = $buyerComiss !== 0 ? '1' : '0';
            $requestTime = now();
            $contractType = $this->resolveContractType($payload);

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
                'buyer_phone' => (string) ($payload['buyer_phone'] ?? '0'),
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
                'contract_type' => $contractType,
                'installment_count' => $this->nullableInteger($payload, 'installment_count'),
                'installment_amount' => $this->nullableInteger($payload, 'installment_amount'),
                'installment_start_date' => $this->nullableString($payload, 'installment_start_date'),
                'installment_end_date' => $this->nullableString($payload, 'installment_end_date'),
                'installment_note' => $this->nullableString($payload, 'installment_note'),
                'requested_at' => $requestTime,
                'approved_at' => $status === 'done' ? $requestTime : null,
                'completed_at' => $status === 'done' ? $requestTime : null,
            ]);

            if ($status === 'done') {
                $this->updateCarSaleState($sale, 3, $actor, $ipAddress);
                $this->recalculateForSale($sale);
            }

            $freshSale = $sale->fresh();

            $this->activityLogger->record(
                $actor,
                'sale.created',
                $freshSale,
                [],
                $this->saleActivityValues($freshSale),
                $ipAddress,
            );

            $this->notifySaleStatus($freshSale, 'created', $status, $ipAddress);

            return $freshSale;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(Sale $sale, array $payload, ?User $actor = null, ?string $ipAddress = null): Sale
    {
        return DB::transaction(function () use ($sale, $payload, $actor, $ipAddress): Sale {
            $previousStatus = (string) $sale->status;
            $previousUserId = (int) $sale->user_id;
            $previousWeekId = (int) $sale->week_id;
            $previousYear = (int) $sale->date->format('Y');
            $oldValues = $this->saleActivityValues($sale);

            $date = (string) $payload['date'];
            $week = $this->accountService->findOrCreateWeekForDate($date);
            $buyerComiss = (int) ($payload['buyer_comiss'] ?? 0);
            $status = $buyerComiss !== 0 ? 'done' : 'hold';
            $requestedAt = $sale->requested_at ?? now();
            $approvedAt = $sale->approved_at;
            $completedAt = $sale->completed_at;
            $contractType = $this->resolveUpdatedContractType($sale, $payload);

            if ($status === 'done') {
                $approvedAt ??= now();
                $completedAt ??= now();
            }

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
                'buyer_phone' => (int) ($payload['buyer_phone'] ?? ''),
                'buyer_comiss' => $buyerComiss,
                'buyer_comiss_payed' => (int) ($payload['buyer_comiss_payed'] ?? 0),
                'owner_id_image' => $this->resolveUpdatedImage($payload['owner_id_image'] ?? null, (string) $sale->owner_id_image),
                'buyer_id_image' => $this->resolveUpdatedImage($payload['buyer_id_image'] ?? null, (string) $sale->buyer_id_image),
                'contract_image' => $this->resolveUpdatedImage($payload['contract_image'] ?? null, (string) $sale->contract_image),
                'date' => $date,
                'week_id' => $week->id,
                'user_id' => (int) $payload['user_id'],
                'status' => $status,
                'requested_at' => $requestedAt,
                'approved_at' => $approvedAt,
                'completed_at' => $completedAt,
                'contract_type' => $contractType,
                'installment_count' => $this->nullableIntegerForUpdate($sale, $payload, 'installment_count'),
                'installment_amount' => $this->nullableIntegerForUpdate($sale, $payload, 'installment_amount'),
                'installment_start_date' => $this->nullableDateForUpdate($sale, $payload, 'installment_start_date'),
                'installment_end_date' => $this->nullableDateForUpdate($sale, $payload, 'installment_end_date'),
                'installment_note' => $this->nullableStringForUpdate($sale, $payload, 'installment_note'),
            ]);

            $updatedSale = $sale->fresh();

            if ($previousStatus === 'done') {
                $this->accountService->recalculateAccountTotal($previousUserId, $previousWeekId, $previousYear);
            }

            if ($status === 'done') {
                $this->updateCarSaleState($updatedSale, 3, $actor, $ipAddress);
                $this->recalculateForSale($updatedSale);
            } elseif ($previousStatus === 'done') {
                $this->updateCarSaleState($updatedSale, 1, $actor, $ipAddress);
            }

            $this->activityLogger->record(
                $actor,
                'sale.updated',
                $updatedSale,
                $oldValues,
                $this->saleActivityValues($updatedSale),
                $ipAddress,
            );

            if ($previousStatus !== 'done' && $status === 'done') {
                $this->notifySaleStatus($updatedSale, 'completed', $status, $ipAddress);
            } elseif ($previousStatus === 'done' && $status === 'hold') {
                $this->notifySaleStatus($updatedSale, 'pending', $status, $ipAddress);
            }

            return $updatedSale;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function complete(Sale $sale, array $payload, ?User $actor = null, ?string $ipAddress = null): Sale
    {
        return DB::transaction(function () use ($sale, $payload, $actor, $ipAddress): Sale {
            $oldValues = $this->saleActivityValues($sale);

            $sale->update([
                'user_comiss' => (int) ($payload['user_comiss'] ?? 0),
                'owner_comiss' => (int) ($payload['owner_comiss'] ?? 0),
                'owner_comiss_payed' => (int) ($payload['owner_comiss_payed'] ?? 0),
                'buyer_comiss' => (int) ($payload['buyer_comiss'] ?? 0),
                'buyer_comiss_payed' => (int) ($payload['buyer_comiss_payed'] ?? 0),
                'employee_name' => (string) ($payload['employee_name'] ?? ''),
                'user_note' => (string) ($payload['user_note'] ?? ''),
                'status' => 'done',
                'approved_at' => $sale->approved_at ?? now(),
                'completed_at' => now(),
            ]);

            $updatedSale = $sale->fresh();
            $user = User::query()->with('gallery')->find($updatedSale->user_id);

            if ($user !== null) {
                $week = Week::query()->find($updatedSale->week_id);

                if ($week !== null) {
                    $this->accountService->ensureAccountExistsForUser($user, $week);
                }
            }

            $this->updateCarSaleState($updatedSale, 3, $actor, $ipAddress);

            $this->recalculateForSale($updatedSale);

            $this->activityLogger->record(
                $actor,
                'sale.completed',
                $updatedSale,
                $oldValues,
                $this->saleActivityValues($updatedSale),
                $ipAddress,
            );

            $this->notifySaleStatus($updatedSale, 'completed', 'done', $ipAddress);

            return $updatedSale;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function approveOrder(Sale $sale, array $payload, ?User $actor = null, ?string $ipAddress = null): Sale
    {
        $oldValues = $this->saleActivityValues($sale);

        $sale->update([
            'approved' => '1',
            'owner_name' => (string) $payload['ownerName'],
            'owner_phone' => (string) $payload['ownerPhone'],
            'car_number' => $payload['carNumber'] ?? null,
            'approved_at' => now(),
        ]);

        $updatedSale = $sale->fresh();

        $this->activityLogger->record(
            $actor,
            'sale.approved',
            $updatedSale,
            $oldValues,
            $this->saleActivityValues($updatedSale),
            $ipAddress,
        );

        $this->notifySaleStatus($updatedSale, 'approved', (string) $updatedSale->status, $ipAddress);

        return $updatedSale;
    }

    public function deleteOrder(Sale $sale, ?User $actor = null, ?string $ipAddress = null): Sale
    {
        return DB::transaction(function () use ($sale, $actor, $ipAddress): Sale {
            $wasDone = (string) $sale->status === 'done';
            $userId = (int) $sale->user_id;
            $weekId = (int) $sale->week_id;
            $year = (int) $sale->date->format('Y');
            $oldValues = $this->saleActivityValues($sale);

            $sale->loadMissing('car');

            if ($sale->car !== null && ! $wasDone) {
                $this->updateCarSaleState($sale, 1, $actor, $ipAddress);
            }

            $this->deleteImage((string) $sale->owner_id_image);
            $this->deleteImage((string) $sale->buyer_id_image);
            $this->deleteImage((string) $sale->contract_image);

            $deletedSale = $sale->replicate();
            $deletedSale->id = $sale->id;

            $sale->delete();

            if ($wasDone) {
                $this->accountService->recalculateAccountTotal($userId, $weekId, $year);
            }

            $this->activityLogger->record(
                $actor,
                'sale.deleted',
                $deletedSale,
                $oldValues,
                [],
                $ipAddress,
            );

            $this->notifySaleStatus($deletedSale, 'deleted', (string) $deletedSale->status, $ipAddress);

            return $deletedSale;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateInstallmentContract(Sale $sale, array $payload, ?User $actor = null, ?string $ipAddress = null): Sale
    {
        return DB::transaction(function () use ($sale, $payload, $actor, $ipAddress): Sale {
            $oldValues = $this->saleActivityValues($sale);

            $sale->update([
                'contract_type' => 'installment',
                'installment_count' => (int) $payload['installment_count'],
                'installment_amount' => (int) $payload['installment_amount'],
                'installment_start_date' => (string) $payload['installment_start_date'],
                'installment_end_date' => (string) $payload['installment_end_date'],
                'installment_note' => (string) ($payload['installment_note'] ?? ''),
            ]);

            $updatedSale = $sale->fresh();

            $this->activityLogger->record(
                $actor,
                'sale.installment_contract.updated',
                $updatedSale,
                $oldValues,
                $this->saleActivityValues($updatedSale),
                $ipAddress,
            );

            $this->notifySaleStatus($updatedSale, 'installment_contract.updated', (string) $updatedSale->status, $ipAddress);

            return $updatedSale;
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

    private function updateCarSaleState(Sale $sale, int $saleState, ?User $actor = null, ?string $ipAddress = null): void
    {
        $sale->loadMissing('car');

        if ($sale->car === null) {
            return;
        }

        $this->carService->updateSaleState($sale->car, $saleState, $actor, $ipAddress);
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveContractType(array $payload): string
    {
        $contractType = (string) ($payload['contract_type'] ?? '');

        if ($contractType !== '') {
            return $contractType;
        }

        if ($this->hasInstallmentPayload($payload)) {
            return 'installment';
        }

        return 'cash';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveUpdatedContractType(Sale $sale, array $payload): string
    {
        if (array_key_exists('contract_type', $payload)) {
            return $this->resolveContractType($payload);
        }

        return (string) ($sale->contract_type ?? 'cash');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasInstallmentPayload(array $payload): bool
    {
        return $this->nullableInteger($payload, 'installment_count') !== null
            || $this->nullableInteger($payload, 'installment_amount') !== null
            || $this->nullableString($payload, 'installment_start_date') !== null
            || $this->nullableString($payload, 'installment_end_date') !== null
            || $this->nullableString($payload, 'installment_note') !== null;
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function nullableIntegerForUpdate(Sale $sale, array $payload, string $key): ?int
    {
        if (! array_key_exists($key, $payload)) {
            return $sale->{$key} !== null ? (int) $sale->{$key} : null;
        }

        return $this->nullableInteger($payload, $key);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function nullableDateForUpdate(Sale $sale, array $payload, string $key): ?string
    {
        if (! array_key_exists($key, $payload)) {
            return $sale->{$key} !== null ? $sale->{$key}->format('Y-m-d') : null;
        }

        return $this->nullableString($payload, $key);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function nullableStringForUpdate(Sale $sale, array $payload, string $key): ?string
    {
        if (! array_key_exists($key, $payload)) {
            return $sale->{$key};
        }

        return $this->nullableString($payload, $key);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function notifySaleStatus(Sale $sale, string $event, string $status, ?string $ipAddress = null): void
    {
        $sale->loadMissing('user');

        $title = match ($event) {
            'created' => $status === 'done' ? 'Sale completed' : 'Pending sale created',
            'pending' => 'Sale returned to pending',
            'completed' => 'Sale completed',
            'approved' => 'Sale approved',
            'deleted' => 'Sale deleted',
            'installment_contract.updated' => 'Installment contract updated',
            default => 'Sale update',
        };

        $body = match ($event) {
            'created' => $status === 'done'
                ? sprintf('Sale #%d was completed on creation.', $sale->id)
                : sprintf('Sale #%d is waiting for approval.', $sale->id),
            'pending' => sprintf('Sale #%d returned to pending status.', $sale->id),
            'completed' => sprintf('Sale #%d was completed.', $sale->id),
            'approved' => sprintf('Sale #%d was approved.', $sale->id),
            'deleted' => sprintf('Sale #%d was deleted.', $sale->id),
            'installment_contract.updated' => sprintf('Installment contract for sale #%d was updated.', $sale->id),
            default => sprintf('Sale #%d changed state.', $sale->id),
        };

        $this->workflowNotificationService->notifyManagers(
            'sale',
            $event,
            $title,
            $body,
            'Sale',
            $sale->id,
            [
                'status' => $status,
                'contract_type' => (string) ($sale->contract_type ?? 'cash'),
                'car_name' => (string) $sale->car_name,
                'gallery_id' => $sale->user?->gallery_id,
                'ip_address' => $ipAddress,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function saleActivityValues(Sale $sale): array
    {
        return $sale->only([
            'user_comiss',
            'user_note',
            'buyer_name',
            'buyer_phone',
            'owner_comiss',
            'owner_comiss_payed',
            'buyer_comiss',
            'buyer_comiss_payed',
            'owner_id_image',
            'buyer_id_image',
            'contract_image',
            'date',
            'week_id',
            'car_brand',
            'car_model',
            'car_name',
            'user_id',
            'car_id',
            'car_number',
            'price',
            'employee_name',
            'owner_name',
            'owner_phone',
            'status',
            'approved',
            'contract_type',
            'installment_count',
            'installment_amount',
            'installment_start_date',
            'installment_end_date',
            'installment_note',
            'requested_at',
            'approved_at',
            'completed_at',
        ]);
    }
}
