<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nasabah extends Model
{
    protected $table = 'nasabah';

    protected $fillable = [
        'customer_id',
        'nasabah_status_id',
        'credit_limit',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function status()
    {
        return $this->belongsTo(NasabahStatus::class, 'nasabah_status_id');
    }
}
