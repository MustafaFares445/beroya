<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_name',
        'client_phone',
        'car_market',
        'car_model',
        'year',
        'price_low',
        'price_high',
        'order_state',
        'order_notes',
        'user_name',
        'gallery_name',
        'checked',
        'approved_at',
        'rejected_at',
        'reviewed_by_user_id',
        'reject_reason',
    ];

    protected function casts(): array
    {
        return [
            'checked' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
