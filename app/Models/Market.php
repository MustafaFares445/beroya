<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    /** @use HasFactory<\Database\Factories\MarketFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'image',
    ];

    public function models(): HasMany
    {
        return $this->hasMany(CarModel::class, 'market_id');
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class, 'market_id');
    }
}
