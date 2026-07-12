<?php

namespace App\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'contract_type' => 'cash',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_comiss',
        'user_note',
        'buyer_name',
        'buyer_phone',
        'owner_comiss',
        'owner_comiss_payed',
        'buyer_comiss',
        'buyer_comiss_payed',
        'owner_id_image',
        'buyer_id_image',
        'contract_image',
        'date',
        'week_id',
        'car_brand',
        'car_model',
        'car_name',
        'user_id',
        'car_id',
        'car_number',
        'price',
        'employee_name',
        'owner_name',
        'owner_phone',
        'status',
        'approved',
        'contract_type',
        'installment_count',
        'installment_amount',
        'installment_start_date',
        'installment_end_date',
        'installment_note',
        'requested_at',
        'approved_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'installment_start_date' => 'date',
            'installment_end_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class, 'week_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }
}
