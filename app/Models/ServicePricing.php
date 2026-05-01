<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type_id',
        'pricing_basis',
        'unit_name',
        'unit_price',
        'min_quantity',
        'max_quantity',
        'discount_pct',
        'is_active',
    ];

    protected $casts = [
        'unit_price' => 'integer', // cents
        'min_quantity' => 'decimal:3',
        'max_quantity' => 'decimal:3',
        'discount_pct' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }
}
