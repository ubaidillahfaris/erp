<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryDisposition extends Model
{
    protected $fillable = [
        'credit_note_item_id',
        'product_id',
        'quantity',
        'action',
        'from_warehouse_id',
        'to_warehouse_id',
        'notes',
        'processed_by',
        'disposed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'disposed_at' => 'datetime',
        ];
    }

    public function creditNoteItem()
    {
        return $this->belongsTo(CreditNoteItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
