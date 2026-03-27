<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restock extends Model
{
    protected $fillable = [
        'tanggal',
        'vendor_id',
        'keterangan',
        'status_pembayaran',
        'total_bayar',
        'biaya_tambahan',
        'total_biaya',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_biaya' => 'decimal:2',
            'total_bayar' => 'decimal:2',
            'biaya_tambahan' => 'array',
        ];
    }

    public function items()
    {
        return $this->hasMany(RestockItem::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
