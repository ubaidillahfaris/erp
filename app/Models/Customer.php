<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToCompany, HasFactory;

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

    public function customerPrices()
    {
        return $this->hasMany(CustomerPrice::class);
    }

    public function creditSetting()
    {
        return $this->hasOne(CustomerCreditSetting::class);
    }

    public function categoryDiscounts()
    {
        return $this->hasMany(CustomerCategoryDiscount::class);
    }
}
