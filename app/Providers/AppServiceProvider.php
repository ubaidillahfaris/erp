<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Journal;
use App\Models\Pengeluaran;
use App\Models\Product;
use App\Models\Production;
use App\Models\Purchase;
use App\Models\Restock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Vendor;
use App\Observers\JournalObserver;
use App\Observers\PengeluaranObserver;
use App\Observers\ProductionObserver;
use App\Observers\ProductObserver;
use App\Observers\PurchaseObserver;
use App\Observers\RestockObserver;
use App\Observers\SaleItemObserver;
use App\Observers\SaleObserver;
use App\Observers\StockMovementObserver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Product::observe(ProductObserver::class);
        StockMovement::observe(StockMovementObserver::class);
        Restock::observe(RestockObserver::class);
        Production::observe(ProductionObserver::class);
        Journal::observe(JournalObserver::class);
        Pengeluaran::observe(PengeluaranObserver::class);
        Sale::observe(SaleObserver::class);
        SaleItem::observe(SaleItemObserver::class);
        Purchase::observe(PurchaseObserver::class);

        // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user, $ability) {
            return $user->hasRole('superadmin') ? true : null;
        });

        Relation::morphMap([
            'vendor' => Vendor::class,
            'customer' => Customer::class,
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
