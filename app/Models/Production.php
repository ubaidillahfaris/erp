<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Production extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'sku',
        'tanggal',
        'bom_id',
        'produk_id',
        'target_yield',
        'actual_yield',
        'status',
        'total_cost',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'target_yield' => 'decimal:4',
            'actual_yield' => 'decimal:4',
            'total_cost' => 'decimal:2',
        ];
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionItem::class);
    }
}
