<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'email',
        'keterangan',
    ];

    public function restocks(): HasMany
    {
        return $this->hasMany(Restock::class);
    }
}
