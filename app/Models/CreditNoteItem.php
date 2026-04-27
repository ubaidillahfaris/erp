<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteItem extends Model
{
    protected $fillable = [
        'credit_note_id',
        'sale_item_id',
        'product_id',
        'quantity_returned',
        'unit_price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity_returned' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
