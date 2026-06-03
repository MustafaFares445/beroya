<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deduction extends Model
{
    /** @use HasFactory<\Database\Factories\DeductionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'deduction';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'amount',
        'description',
        'accountant_id',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountant_id');
    }
}
