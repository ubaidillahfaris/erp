<?php

namespace App\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    /**
     * Boot the trait to add global scope and creating observer.
     */
    public static function bootBelongsToCompany(): void
    {
        // Global scope to filter by company_id
        static::addGlobalScope('company', function (Builder $builder) {
            // Use hasUser() to avoid infinite loop when retrieving the authenticated user
            if (Auth::hasUser()) {
                $user = Auth::user();

                // Superadmins can see data across all companies
                if ($user->hasRole('superadmin')) {
                    return;
                }

                if ($user->company_id) {
                    // We use the table name prefix to avoid ambiguity in joins
                    $builder->where($builder->getQuery()->from.'.company_id', $user->company_id);
                }
            }
        });

        // Automatically assign company_id when creating
        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->company_id && ! $model->company_id) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    /**
     * Relationship to the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
