<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_comiss' => $this->user_comiss,
            'user_note' => $this->user_note,
            'buyer_name' => $this->buyer_name,
            'buyer_phone' => $this->buyer_phone,
            'owner_comiss' => $this->owner_comiss,
            'owner_comiss_payed' => $this->owner_comiss_payed,
            'buyer_comiss' => $this->buyer_comiss,
            'buyer_comiss_payed' => $this->buyer_comiss_payed,
            'owner_id_image' => $this->mediaUrl($this->owner_id_image),
            'buyer_id_image' => $this->mediaUrl($this->buyer_id_image),
            'contract_image' => $this->mediaUrl($this->contract_image),
            'date' => $this->date?->format('Y-m-d'),
            'week_id' => $this->week_id,
            'car_brand' => $this->car_brand,
            'car_model' => $this->car_model,
            'car_name' => $this->car_name,
            'user_id' => $this->user_id,
            'car_id' => $this->car_id,
            'car_number' => $this->car_number,
            'price' => $this->price,
            'employee_name' => $this->employee_name,
            'owner_name' => $this->owner_name,
            'owner_phone' => $this->owner_phone,
            'status' => $this->status,
            'requested_at' => $this->requested_at?->format('Y-m-d H:i:s'),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'contract_type' => $this->contract_type,
            'installment_count' => $this->installment_count,
            'installment_amount' => $this->installment_amount,
            'installment_start_date' => $this->installment_start_date?->format('Y-m-d'),
            'installment_end_date' => $this->installment_end_date?->format('Y-m-d'),
            'installment_note' => $this->installment_note,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'approved' => $this->approved,
        ];
    }

    private function mediaUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $path = str_starts_with($path, 'sales/') ? $path : 'sales/'.$path;
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            return null;
        }

        return $disk->temporaryUrl($path, now()->addMinutes(30));
    }
}
