<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bom extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'is_active',
        'expected_yield',
        'yield_unit_id',
        'auto_deduct_on_sale',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'auto_deduct_on_sale' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Bom $bom) {
            if (! $bom->yield_unit_id && $bom->product_id) {
                $bom->yield_unit_id = $bom->product->unit_id;
            }
        });
    }

    /**
     * Get the finished product that owns the BOM.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the unit for the yield total.
     */
    public function yieldUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'yield_unit_id');
    }

    /**
     * Get the items (ingredients) for the BOM.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }
}
