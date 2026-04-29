<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'customer_id',
        'sale_id',
        'order_type',
        'order_number',
        'status',
        'estimated_at',
        'ready_at',
        'picked_up_at',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'estimated_at' => 'datetime',
        'ready_at' => 'datetime',
        'picked_up_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class);
    }

    // Helper for Laundry: get weight from metadata
    public function getWeightKgAttribute(): ?float
    {
        return $this->metadata['weight_kg'] ?? null;
    }

    // Helper for Laundry: get service type from metadata
    public function getServiceTypeAttribute(): ?string
    {
        return $this->metadata['service_type'] ?? null;
    }
}
