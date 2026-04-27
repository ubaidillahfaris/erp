<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'notes',
        'status',
        'storno_at',
        'storno_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'storno_at' => 'datetime',
        ];
    }

    /**
     * Get the items for the stock opname.
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}
