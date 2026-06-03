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
        ];
    }
}
