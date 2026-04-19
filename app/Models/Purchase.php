<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'no_invoice',
        'vendor_id',
        'tanggal',
        'transaction_type',
        'payment_method',
        'status',
        'total_biaya',
        'keterangan',
        'signature_log',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'signature_log' => 'array',
            'total_biaya' => 'decimal:2',
        ];
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function attachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseAttachment::class);
    }

    public function vendor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
