<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPriceHistory extends Model
{
    protected $fillable = [
        'customer_price_id',
        'customer_id',
        'produk_id',
        'satuan_id',
        'old_price',
        'new_price',
        'old_valid_until',
        'new_valid_until',
        'action',
        'changed_by',
    ];

    /**
     * Get the customer price that this history belongs to.
     */
    public function customerPrice(): BelongsTo
    {
        return $this->belongsTo(CustomerPrice::class);
    }

    /**
     * Get the customer associated with this history.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the product associated with this history.
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    /**
     * Get the unit associated with this history.
     */
    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class);
    }

    /**
     * Get the user who made the change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
