<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceProcessingStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'status_code',
        'status_name',
        'sequence_order',
        'is_default_start',
        'is_final',
    ];

    protected $casts = [
        'sequence_order' => 'integer',
        'is_default_start' => 'boolean',
        'is_final' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
