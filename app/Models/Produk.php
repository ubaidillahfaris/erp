<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Produk extends Model
{
    /** @use HasFactory<\Database\Factories\ProdukFactory> */
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
    ];

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
    public function bom(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Bom::class, 'produk_id');
    }

    /**
     * Get all prices for the product.
     */
    public function prices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Price::class, 'produk_id');
    }

    /**
     * Get the current price for the product.
     */
    public function currentPrice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Price::class, 'produk_id')->where('is_current', true);
    }

    /**
     * Get all stock movements for the product.
     */
    public function stockMovements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockMovement::class, 'produk_id');
    }

    /**
     * Get the stock summary for the product.
     */
    public function stock(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Stock::class, 'produk_id');
    }
}
