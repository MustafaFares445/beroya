<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
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
            'offer_number' => $this->offer_number,
            'province_id' => $this->province_id,
            'province_name' => $this->province?->name,
            'office_id' => $this->office_id,
            'office_name' => $this->office?->name,
            'main_category_id' => $this->main_category_id,
            'main_category_name' => $this->mainCategory?->name,
            'subcategory_id' => $this->subcategory_id,
            'subcategory_name' => $this->subcategory?->name,
            'property_nature' => $this->property_nature,
            'title_type' => $this->title_type,
            'area' => $this->area,
            'district' => $this->district,
            'address' => $this->address,
            'building' => $this->building,
            'floor' => $this->floor,
            'direction' => $this->direction,
            'rooms_count' => $this->rooms_count,
            'area_size' => $this->area_size,
            'price' => $this->price,
            'ownership_type' => $this->ownership_type,
            'offer_type' => $this->offer_type,
            'rent_duration' => $this->rent_duration,
            'owner_name' => $this->owner_name,
            'owner_phone' => $this->owner_phone,
            'status' => $this->status,
            'images' => PropertyImageResource::collection($this->images)->resolve(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
