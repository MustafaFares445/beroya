<?php

namespace App\Models;

use Database\Factories\PropertySubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertySubmission extends Model
{
    /** @use HasFactory<PropertySubmissionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'offer_number',
        'province_id',
        'office_id',
        'main_category_id',
        'subcategory_id',
        'property_nature',
        'title_type',
        'area',
        'district',
        'address',
        'building',
        'floor',
        'direction',
        'rooms_count',
        'area_size',
        'price',
        'ownership_type',
        'offer_type',
        'rent_duration',
        'owner_name',
        'owner_phone',
        'submission_note',
        'status',
        'reject_reason',
        'published_property_id',
        'reviewed_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'rooms_count' => 'integer',
            'area_size' => 'integer',
            'price' => 'integer',
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(RealEstateOffice::class, 'office_id');
    }

    public function mainCategory(): BelongsTo
    {
        return $this->belongsTo(PropertyCategory::class, 'main_category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(PropertySubcategory::class, 'subcategory_id');
    }

    public function publishedProperty(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'published_property_id');
    }
}
