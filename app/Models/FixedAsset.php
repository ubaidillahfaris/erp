<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'asset_code',
        'name',
        'description',
        'category',
        'acquisition_date',
        'acquisition_cost',
        'useful_life_months',
        'salvage_value',
        'current_book_value',
        'status',
        'asset_account_id',
        'depreciation_account_id',
        'expense_account_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'acquisition_cost' => 'integer',
            'salvage_value' => 'integer',
            'current_book_value' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($asset) {
            if (! $asset->asset_code) {
                $asset->asset_code = self::generateAssetCode();
            }
            if (! $asset->created_by) {
                $asset->created_by = auth()->id() ?? 1;
            }
        });
    }

    public static function generateAssetCode(): string
    {
        $prefix = 'AST-';
        $year = now()->format('Y');
        $lastAsset = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = 1;
        if ($lastAsset && preg_match('/AST-\d{4}-(\d{4})/', $lastAsset->asset_code, $matches)) {
            $number = (int) $matches[1] + 1;
        }

        return sprintf('%s%s-%04d', $prefix, $year, $number);
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function depreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DepreciationSchedule::class);
    }
}
