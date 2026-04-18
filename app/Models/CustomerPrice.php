<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPrice extends Model
{
    protected $fillable = [
        'customer_id',
        'produk_id',
        'satuan_id',
        'custom_price',
        'valid_until',
        'is_active',
    ];

    /**
     * Get the customer that owns the custom price.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the product that owns the custom price.
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    /**
     * Get the unit that owns the custom price.
     */
    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class);
    }
}
