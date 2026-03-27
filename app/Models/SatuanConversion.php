<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatuanConversion extends Model
{
    protected $fillable = [
        'satuan_id',
        'to_satuan_id',
        'produk_id',
        'rasio',
    ];

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    /**
     * Get the source unit.
     */
    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    /**
     * Get the target unit.
     */
    public function toSatuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'to_satuan_id');
    }
}
