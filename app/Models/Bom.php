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
        'produk_id',
        'sku',
        'nama',
        'is_active',
        'expected_yield',
        'yield_satuan_id',
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
            if (! $bom->yield_satuan_id && $bom->produk_id) {
                $bom->yield_satuan_id = $bom->produk->satuan_id;
            }
        });
    }

    /**
     * Get the finished product that owns the BOM.
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    /**
     * Get the unit for the yield total.
     */
    public function yieldSatuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'yield_satuan_id');
    }

    /**
     * Get the items (ingredients) for the BOM.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }
}
