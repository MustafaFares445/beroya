<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;

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
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
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
