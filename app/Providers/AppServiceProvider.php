<?php

namespace App\Providers;

use App\Models\Produk;
use Illuminate\Support\Facades\Gate;
use App\Models\Restock;
use App\Models\Production;
use App\Models\StockMovement;
use App\Models\Journal;
use App\Models\Pengeluaran;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Observers\ProdukObserver;
use App\Observers\RestockObserver;
use App\Observers\ProductionObserver;
use App\Observers\StockMovementObserver;
use App\Observers\JournalObserver;
use App\Observers\PengeluaranObserver;
use App\Observers\SaleObserver;
use App\Observers\SaleItemObserver;
use App\Observers\PurchaseObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Customer;
use App\Models\Vendor;

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

        Produk::observe(ProdukObserver::class);
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
            fn(): ?Password => app()->isProduction()
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
