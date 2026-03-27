<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestockItem extends Model
{
    protected $fillable = [
        'restock_id',
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

    public function restock()
    {
        return $this->belongsTo(Restock::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }
}
