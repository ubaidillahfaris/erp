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
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
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
}
