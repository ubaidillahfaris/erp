<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterestSchedule extends Model
{
    protected $fillable = [
        'payable_id',
        'period_number',
        'due_date',
        'principal_portion',
        'interest_portion',
        'total_due',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'principal_portion' => 'decimal:2',
            'interest_portion' => 'decimal:2',
            'total_due' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }
}
