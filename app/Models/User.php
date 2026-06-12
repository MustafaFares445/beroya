<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_name',
        'password',
        'gallery_id',
        'real_estate_office_id',
        'real_estate_role',
        'permetions_level',
        'salary',
        'phone',
        'last_login',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_login' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'gallery_id');
    }

    public function realEstateOffice(): BelongsTo
    {
        return $this->belongsTo(RealEstateOffice::class, 'real_estate_office_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'user_id');
    }
}
