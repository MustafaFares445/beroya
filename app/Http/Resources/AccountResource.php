<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'user_position' => $this->user_position,
            'user_gallery' => $this->user_gallery,
            'sales_count' => $this->sales_count,
            'sales_amount' => $this->sales_amount,
            'deduction_amount' => $this->deduction_amount,
            'working_days_count' => $this->working_days_count,
            'salary' => $this->getAttribute('salary') ?? $this->salary,
            'week_id' => $this->week_id,
            'year' => $this->year,
            'total_amount' => $this->total_amount,
            'received' => $this->received,
        ];
    }
}
