<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'produk_id',
        'satuan_id',
        'jumlah',
        'harga_satuan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:4',
            'harga_satuan' => 'decimal:2',
        ];
    }

    public function purchase(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function produk(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    public function satuan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Satuan::class);
    }
}
