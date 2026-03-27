<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class Journal extends Model
{
    use HasFactory;
    protected $fillable = [
        'tanggal',
        'type',
        'amount',
        'balance',
        'category',
        'payment_method',
        'reference_type',
        'reference_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance' => 'integer',
        ];
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
