<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'car_market' => $this->car_market,
            'car_model' => $this->car_model,
            'year' => $this->year,
            'price_low' => $this->price_low,
            'price_high' => $this->price_high,
            'order_state' => $this->order_state,
            'order_notes' => $this->order_notes,
            'user_name' => $this->user_name,
            'gallery_name' => $this->gallery_name,
            'checked' => $this->checked,
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'rejected_at' => $this->rejected_at?->format('Y-m-d H:i:s'),
            'reviewed_by_user_id' => $this->reviewed_by_user_id,
            'reject_reason' => $this->reject_reason,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
