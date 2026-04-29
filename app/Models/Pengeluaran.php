<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'date',
        'jenis_pengeluaran',
        'account_id',
        'nama_pengeluaran',
        'nominal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'nominal' => 'decimal:2',
            'account_id' => 'integer',
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
