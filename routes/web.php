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
use App\Http\Controllers\QuickCreateVendorController;
use App\Http\Controllers\PurchaseController;

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

        // Master Data
        Route::delete('produk/bulk-destroy', [ProdukController::class, 'bulkDestroy'])->name('produk.bulk-destroy');
        Route::resource('produk', ProdukController::class)->names('produk');

        Route::delete('satuan/bulk-destroy', [SatuanController::class, 'bulkDestroy'])->name('satuan.bulk-destroy');
        Route::resource('satuan', SatuanController::class);
        Route::post('satuan/quick', QuickCreateSatuanController::class)->name('satuan.quick');
        Route::post('vendor/quick', QuickCreateVendorController::class)->name('vendor.quick');


        Route::delete('vendors/bulk-destroy', [VendorController::class, 'bulkDestroy'])->name('vendor.bulk-destroy');
        Route::resource('vendors', VendorController::class)->names('vendor');

        Route::delete('bom/bulk-destroy', [BOMController::class, 'bulkDestroy'])->name('bom.bulk-destroy');
        Route::resource('bom', BOMController::class);

        // Procurement & Expenses
        Route::delete('restock/bulk-destroy', [RestockController::class, 'bulkDestroy'])->name('restock.bulk-destroy');
        Route::resource('restock', RestockController::class);
        Route::post('restock/{restock}/settle', [RestockController::class, 'settle'])->name('restock.settle');

        // Purchasing (Formal Procurement with Attachments & Finalization)
        Route::resource('purchasing', PurchaseController::class)->parameters([
            'purchasing' => 'purchase'
        ]);
        Route::post('purchasing/{purchase}/finalize', [PurchaseController::class, 'finalize'])->name('purchasing.finalize');


        Route::delete('purchasing-attachment/{purchaseAttachment}', [PurchaseController::class, 'destroyAttachment'])->name('purchasing.attachment.destroy');

        Route::delete('pengeluaran/bulk-destroy', [PengeluaranController::class, 'bulkDestroy'])->name('pengeluaran.bulk-destroy');
        Route::resource('pengeluaran', PengeluaranController::class);

        // Production
        Route::delete('production/bulk-destroy', [ProductionController::class, 'bulkDestroy'])->name('production.bulk-destroy');
        Route::resource('production', ProductionController::class);

        // Financials & Reports
        Route::get('journal', [JournalController::class, 'index'])->name('journal.index');
        Route::get('profit-loss', [ProfitLossController::class, 'index'])->name('profit-loss.index');

        // Stocks
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/{produk}', [StockController::class, 'show'])->name('stock.show');
        Route::post('stock/adjustment', [StockController::class, 'adjustment'])->name('stock.adjustment');
        Route::resource('stock-opname', StockOpnameController::class);
    });
});

require __DIR__.'/settings.php';
