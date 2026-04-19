<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    protected $fillable = [
        'tanggal',
        'jenis_pengeluaran',
        'account_id',
        'nama_pengeluaran',
        'nominal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'decimal:2',
            'account_id' => 'integer',
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
