<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_id',
        'product_id',
        'unit_id',
        'planned_qty',
        'actual_qty',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'planned_qty' => 'decimal:4',
            'actual_qty' => 'decimal:4',
            'unit_price' => 'decimal:2',
        ];
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
