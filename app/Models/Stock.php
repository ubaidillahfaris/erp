<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'last_unit_id',
        'balance',
        'last_movement_id',
        'condition',
        'is_sellable',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:4',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lastUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'last_unit_id');
    }

    public function lastMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'last_movement_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
