<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'category' => data_get($this->data, 'category'),
            'event' => data_get($this->data, 'event'),
            'title' => data_get($this->data, 'title'),
            'body' => data_get($this->data, 'body'),
            'entity_type' => data_get($this->data, 'entity_type'),
            'entity_id' => data_get($this->data, 'entity_id'),
            'meta' => data_get($this->data, 'meta', []),
            'read_at' => $this->read_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
