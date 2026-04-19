<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'invoice_number',
        'tanggal',
        'total_amount',
        'received_amount',
        'change_amount',
        'payment_method',
        'notes',
        'status',
        'storno_at',
        'storno_reason',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'storno_at' => 'datetime',
        ];
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function journals(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Journal::class, 'reference');
    }

    public function saleCustomer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SaleCustomer::class);
    }

    public function payable(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Payable::class, 'reference_id')
            ->where('reference_type', 'sale');
    }
}
