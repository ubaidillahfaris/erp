<?php

namespace App\Models;

use Database\Factories\SatuanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Satuan extends Model
{
    /** @use HasFactory<SatuanFactory> */
    use HasFactory;

    protected $fillable = [
        'nama',
        'simbol',
        'deskripsi',
    ];

    /**
     * Get the products for the unit.
     */
    public function produks(): HasMany
    {
        return $this->hasMany(Produk::class);
    }

    /**
     * Get the conversions for this unit.
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(SatuanConversion::class);
    }
}
