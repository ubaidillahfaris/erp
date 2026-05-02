<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionStep extends Model
{
    use Auditable, BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'parent_step_id',
        'name',
        'code',
        'sequence_order',
        'is_start',
        'is_final',
        'is_active',
        'color_hex',
    ];

    protected $casts = [
        'is_start' => 'boolean',
        'is_final' => 'boolean',
        'is_active' => 'boolean',
        'sequence_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductionStep::class, 'parent_step_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductionStep::class, 'parent_step_id');
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }
}
