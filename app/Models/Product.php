<?php

namespace App\Models;

use App\Traits\Auditable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use Auditable, HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'category_id',
        'description',
        'min_stock',
        'is_active',
        'unit_id',
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
            'name' => $this->name,
            'category' => $this->category?->name,
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
            'min_stock' => 'integer',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
            'overhead_rate_per_unit' => 'integer',
        ];
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the unit that owns the product.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get the BOM recipe for the product.
     */
    public function bom(): HasOne
    {
        return $this->hasOne(Bom::class, 'product_id');
    }

    /**
     * Get all prices for the product.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(Price::class, 'product_id');
    }

    /**
     * Get the current price for the product.
     */
    public function currentPrice(): HasOne
    {
        return $this->hasOne(Price::class, 'product_id')->where('is_current', true);
    }

    /**
     * Get all stock movements for the product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    /**
     * Get the price statistics for the product.
     */
    public function priceStats(): HasMany
    {
        return $this->hasMany(ProductPriceStat::class, 'product_id');
    }

    /**
     * Get all stock records for the product (one per warehouse).
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'product_id');
    }

    /**
     * Get a specific stock record for the product (context dependent).
     */
    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class, 'product_id');
    }

    public function customerPrices(): HasMany
    {
        return $this->hasMany(CustomerPrice::class, 'product_id');
    }
}
