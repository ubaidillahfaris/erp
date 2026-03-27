<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialSummary extends Model
{
    protected $fillable = [
        'date',
        'total_debit',
        'total_kredit',
        'final_balance',
    ];

    protected function casts(): array
    {
        return [
            'total_debit' => 'decimal:2',
            'total_kredit' => 'decimal:2',
            'final_balance' => 'decimal:2',
        ];
    }
}
