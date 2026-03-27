<?php

namespace App\Providers;

use App\Models\Produk;
use App\Models\Restock;
use App\Models\Production;
use App\Models\StockMovement;
use App\Models\Journal;
use App\Models\Pengeluaran;
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
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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

        Produk::observe(ProdukObserver::class);
        StockMovement::observe(StockMovementObserver::class);
        Restock::observe(RestockObserver::class);
        Production::observe(ProductionObserver::class);
        Journal::observe(JournalObserver::class);
        Pengeluaran::observe(PengeluaranObserver::class);
        Sale::observe(SaleObserver::class);
        SaleItem::observe(SaleItemObserver::class);
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
