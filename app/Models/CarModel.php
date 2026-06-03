<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarModel extends Model
{
    /** @use HasFactory<\Database\Factories\CarModelFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'models';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'market_id',
    ];

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class, 'market_id');
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class, 'model_id');
    }
}
