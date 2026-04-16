<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $fillable = [
        'tanggal',
        'keterangan',
        'status',
        'storno_at',
        'storno_reason',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'storno_at' => 'datetime',
        ];
    }

    /**
     * Get the items for the stock opname.
     */
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}
