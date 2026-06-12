<?php

namespace App\Models;

use Database\Factories\PropertyCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyCategory extends Model
{
    /** @use HasFactory<PropertyCategoryFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    public function subcategories(): HasMany
    {
        return $this->hasMany(PropertySubcategory::class, 'property_category_id');
    }
}
