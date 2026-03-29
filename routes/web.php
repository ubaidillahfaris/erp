<?php

use App\Http\Controllers\BOMController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ProfitLossController;
use App\Http\Controllers\QuickCreateSatuanController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\SatuanController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified', 'dynamic_menu'])->group(function () {
    // POS (Point of Sale) - Accessible by both superadmin and cashier
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('pos', [PosController::class, 'store'])->name('pos.store');

    // Admin-only Access
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::delete('produk/bulk-destroy', [ProdukController::class, 'bulkDestroy'])->name('produk.bulk-destroy');
        Route::resource('produk', ProdukController::class)->names('produk');
        Route::resource('satuan', SatuanController::class);
        Route::resource('bom', BOMController::class);
        Route::resource('restock', RestockController::class);
        Route::post('restock/{restock}/settle', [RestockController::class, 'settle'])->name('restock.settle');
        Route::resource('pengeluaran', PengeluaranController::class);
        Route::delete('vendors/bulk-destroy', [VendorController::class, 'bulkDestroy'])->name('vendor.bulk-destroy');
        Route::resource('vendors', VendorController::class)->names('vendor')->parameters(['vendors' => 'vendor']);
        Route::get('journal', [JournalController::class, 'index'])->name('journal.index');
        Route::get('profit-loss', [ProfitLossController::class, 'index'])->name('profit-loss.index');
        Route::resource('production', ProductionController::class);
        Route::post('satuan/quick', QuickCreateSatuanController::class)->name('satuan.quick');

        // Stocks
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/{produk}', [StockController::class, 'show'])->name('stock.show');
        Route::post('stock/adjustment', [StockController::class, 'adjustment'])->name('stock.adjustment');
        Route::resource('stock-opname', StockOpnameController::class);
    });
});

require __DIR__.'/settings.php';
