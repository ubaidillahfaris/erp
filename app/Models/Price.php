<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $fillable = [
        'produk_id',
        'satuan_id',
        'purchase_price',
        'retail_price',
        'wholesale_price',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'is_current' => 'boolean',
        ];
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }
}
