<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseAttachment extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'purchase_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];

    public function purchase(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
