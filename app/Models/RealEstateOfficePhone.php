<?php

namespace App\Models;

use Database\Factories\RealEstateOfficePhoneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealEstateOfficePhone extends Model
{
    /** @use HasFactory<RealEstateOfficePhoneFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'real_estate_office_id',
        'phone',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(RealEstateOffice::class, 'real_estate_office_id');
    }
}
