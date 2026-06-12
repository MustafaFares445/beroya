<?php

namespace App\Models;

use Database\Factories\RealEstateOfficeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RealEstateOffice extends Model
{
    /** @use HasFactory<RealEstateOfficeFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'province_id',
        'name',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function phones(): HasMany
    {
        return $this->hasMany(RealEstateOfficePhone::class, 'real_estate_office_id');
    }
}
