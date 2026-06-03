<?php

namespace App\Http\Resources;

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
            'permetions_level' => $this->permetions_level,
            'salary' => $this->salary,
            'phone' => $this->phone,
            'last_login' => $this->last_login?->format('Y-m-d H:i:s'),
            'is_active' => $this->is_active,
        ];
    }
}
