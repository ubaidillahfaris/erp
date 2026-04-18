<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NasabahStatus extends Model
{
    protected $fillable = ['name'];

    public function nasabah()
    {
        return $this->hasMany(Nasabah::class);
    }
}
