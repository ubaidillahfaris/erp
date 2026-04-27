<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Production extends Model
{
    use Auditable, HasFactory, Searchable;

    protected $fillable = [
        'sku',
        'date',
        'bom_id',
        'product_id',
        'target_yield',
        'actual_yield',
        'status',
        'total_cost',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'target_yield' => 'decimal:4',
            'actual_yield' => 'decimal:4',
            'total_cost' => 'decimal:2',
        ];
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionItem::class);
    }
}
