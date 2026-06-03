<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'accountants';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'user_position',
        'user_gallery',
        'sales_count',
        'sales_amount',
        'deduction_amount',
        'working_days_count',
        'salary',
        'week_id',
        'year',
        'total_amount',
        'received',
    ];

    protected function casts(): array
    {
        return [
            'sales_count' => 'integer',
            'sales_amount' => 'integer',
            'deduction_amount' => 'integer',
            'working_days_count' => 'integer',
            'salary' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class, 'week_id');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(Deduction::class, 'accountant_id');
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(Bonus::class, 'accountant_id');
    }
}
