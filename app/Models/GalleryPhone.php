<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryPhone extends Model
{
    /** @use HasFactory<\Database\Factories\GalleryPhoneFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'galleries_phones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'phone',
        'gallery_id',
    ];

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'gallery_id');
    }
}
