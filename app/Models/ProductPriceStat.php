<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceStat extends Model
{
    protected $fillable = [
        'product_id',
        'unit_id',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
