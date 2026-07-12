<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
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
            'actor_user_id' => $this->actor_user_id,
            'actor_user_name' => $this->actor?->user_name,
            'gallery_id' => $this->gallery_id,
            'gallery_name' => $this->gallery?->name,
            'action_type' => $this->action_type,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'old_values' => $this->old_values ?? [],
            'new_values' => $this->new_values ?? [],
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
