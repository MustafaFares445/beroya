<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
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
            'market_id' => $this->market_id,
            'model_id' => $this->model_id,
            'year' => $this->year,
            'gasoline' => $this->gasoline,
            'engine' => $this->engine,
            'transmission' => $this->transmission,
            'color' => $this->color,
            'distance' => $this->distance,
            'imported' => $this->imported,
            'spray' => $this->spray,
            'status' => $this->status,
            'description' => $this->description,
            'plateNumber' => $this->plateNumber,
            'notes' => $this->notes,
            'price' => $this->price,
            'possession' => $this->possession,
            'owner_name' => $this->owner_name,
            'owner_phone' => $this->owner_phone,
            'gallery_id' => $this->gallery_id,
            'image_1' => $this->image_1,
            'image_2' => $this->image_2,
            'image_3' => $this->image_3,
            'image_4' => $this->image_4,
            'image_5' => $this->image_5,
            'image_6' => $this->image_6,
            'car_sale_state' => $this->car_sale_state,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
