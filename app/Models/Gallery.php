<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gallery extends Model
{
    /** @use HasFactory<\Database\Factories\GalleryFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
    ];

    public function phones(): HasMany
    {
        return $this->hasMany(GalleryPhone::class, 'gallery_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'gallery_id');
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class, 'gallery_id');
    }
}
