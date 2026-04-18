<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleCustomer extends Model
{
    protected $fillable = [
        'sale_id',
        'customer_id',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
