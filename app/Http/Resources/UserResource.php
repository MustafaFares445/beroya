<?php

namespace App\Http\Resources;

use App\Support\RealEstate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'user_name' => $this->user_name,
            'gallery_id' => $this->gallery_id,
            'real_estate_province_id' => $this->real_estate_province_id ?? $this->realEstateOffice?->province?->id,
            'real_estate_province_name' => $this->realEstateProvince?->name ?? $this->realEstateOffice?->province?->name,
            'real_estate_office_id' => $this->real_estate_office_id,
            'real_estate_office_name' => $this->realEstateOffice?->name,
            'real_estate_role' => $this->real_estate_role,
            'real_estate_role_label' => RealEstate::roleLabel(
                $this->real_estate_role,
                $this->permetions_level !== null ? (int) $this->permetions_level : null
            ),
            'permetions_level' => $this->permetions_level,
            'salary' => $this->salary,
            'phone' => $this->phone,
            'last_login' => $this->last_login?->format('Y-m-d H:i:s'),
            'is_active' => $this->is_active,
        ];
    }
}
