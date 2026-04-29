<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockBatch extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'unit_id',
        'batch_number',
        'lot_number',
        'expiry_date',
        'quantity_on_hand',
        'quantity_reserved',
        'received_at',
        'source_type',
        'source_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'quantity_on_hand' => 'decimal:4',
            'quantity_reserved' => 'decimal:4',
            'received_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Scope for FEFO (First Expired First Out)
     */
    public function scopeFefo($query)
    {
        return $query->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->orderBy('received_at', 'ASC');
    }

    /**
     * Scope for available stock
     */
    public function scopeAvailable($query)
    {
        return $query->where('quantity_on_hand', '>', 0);
    }

    /**
     * Get dynamic status based on expiry date
     */
    public function getStatusAttribute($value): string
    {
        if ($this->expiry_date === null) {
            return $value ?: 'ok';
        }

        if ($this->expiry_date->isPast()) {
            return 'expired';
        }

        if ($this->expiry_date->diffInDays(now()) <= 30) {
            return 'expiring_soon';
        }

        return 'ok';
    }
}
