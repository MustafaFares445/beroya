<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    public $timestamps = false;

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
    ];
}
