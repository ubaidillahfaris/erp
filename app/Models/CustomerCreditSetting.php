<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCreditSetting extends Model
{
    protected $fillable = [
        'customer_id',
        'allow_credit',
        'credit_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'allow_credit' => 'boolean',
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
