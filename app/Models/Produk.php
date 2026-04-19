<?php

namespace App\Models;

use Database\Factories\ProdukFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

class Produk extends Model
{
    /** @use HasFactory<ProdukFactory> */
    use HasFactory, Searchable;

    protected $fillable = [
        'sku',
        'barcode',
        'nama',
        'kategori',
        'deskripsi',
        'stok_minimal',
        'is_active',
        'satuan_id',
        'type',
        'track_stock',
        'overhead_rate_per_unit',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'sku' => $this->sku,
            'nama' => $this->nama,
            'kategori' => $this->kategori,
            'type' => $this->type,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stok_minimal' => 'integer',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
            'overhead_rate_per_unit' => 'integer',
        ];
    }

    /**
     * Get the unit that owns the product.
     */
    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    /**
     * Get the BOM recipe for the product.
     */
    public function bom(): HasOne
    {
        return $this->hasOne(Bom::class, 'produk_id');
    }

    /**
     * Get all prices for the product.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(Price::class, 'produk_id');
    }

    /**
     * Get the current price for the product.
     */
    public function currentPrice(): HasOne
    {
        return $this->hasOne(Price::class, 'produk_id')->where('is_current', true);
    }

    /**
     * Get all stock movements for the product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'produk_id');
    }

    /**
     * Get the price statistics for the product.
     */
    public function priceStats(): HasMany
    {
        return $this->hasMany(ProductPriceStat::class, 'produk_id');
    }

    /**
     * Get the stock summary for the product.
     */
    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class, 'produk_id');
    }

    public function customerPrices(): HasMany
    {
        return $this->hasMany(CustomerPrice::class, 'produk_id');
    }
}
