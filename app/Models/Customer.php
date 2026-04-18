<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'customer_type_id',
        'customer_status_id',
        'name',
        'phone',
        'email',
        'address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function status()
    {
        return $this->belongsTo(CustomerStatus::class, 'customer_status_id');
    }

    public function nasabah()
    {
        return $this->hasOne(Nasabah::class);
    }

    public function sales()
    {
        return $this->hasMany(SaleCustomer::class);
    }
}
