<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestockItem extends Model
{
    protected $fillable = [
        'restock_id',
        'product_id',
        'unit_id',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
        ];
    }

    public function restock()
    {
        return $this->belongsTo(Restock::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
