<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'name',
        'symbol',
        'description',
    ];

    /**
     * Get the products for the unit.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the conversions for this unit.
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(UnitConversion::class);
    }
}
