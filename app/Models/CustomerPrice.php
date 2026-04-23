<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Get the histories for the customer price.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(CustomerPriceHistory::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (CustomerPrice $customerPrice) {
            $customerPrice->recordHistory('created');
        });

        static::updated(function (CustomerPrice $customerPrice) {
            // Detect if is_active was changed to false, treating it as 'deleted' action
            $action = ($customerPrice->wasChanged('is_active') && ! $customerPrice->is_active)
                ? 'deleted'
                : 'updated';

            $customerPrice->recordHistory($action);
        });

        static::deleted(function (CustomerPrice $customerPrice) {
            $customerPrice->recordHistory('deleted');
        });
    }

    /**
     * Record history for the customer price.
     */
    protected function recordHistory(string $action): void
    {
        CustomerPriceHistory::create([
            'customer_price_id' => $this->id,
            'customer_id' => $this->customer_id,
            'produk_id' => $this->produk_id,
            'satuan_id' => $this->satuan_id,
            'old_price' => $action === 'created' ? null : $this->getOriginal('custom_price'),
            'new_price' => $action === 'deleted' ? null : $this->custom_price,
            'old_valid_until' => $action === 'created' ? null : $this->getOriginal('valid_until'),
            'new_valid_until' => $action === 'deleted' ? null : $this->valid_until,
            'action' => $action,
            'changed_by' => auth()->id(),
        ]);
    }

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
