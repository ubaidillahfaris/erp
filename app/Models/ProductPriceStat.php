<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceStat extends Model
{
    protected $fillable = [
        'produk_id',
        'satuan_id',
        'avg_price',
        'min_price',
        'max_price',
        'last_purchase_price',
    ];

    protected function casts(): array
    {
        return [
            'avg_price' => 'decimal:2',
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'last_purchase_price' => 'decimal:2',
        ];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class);
    }
}
