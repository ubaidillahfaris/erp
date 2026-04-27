<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitConversion extends Model
{
    protected $fillable = [
        'unit_id',
        'target_unit_id',
        'product_id',
        'ratio',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the source unit.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get the target unit.
     */
    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'target_unit_id');
    }
}
