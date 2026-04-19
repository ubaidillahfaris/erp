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
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\CustomerPriceController;
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
    // Accessible by anyone with 'view dashboard' permission
    Route::middleware('permission:view dashboard')->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 0. POINT OF SALE (make sales)
    Route::middleware('permission:make sales')->group(function () {
        Route::get('pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('pos/price', [PosController::class, 'getPrice'])->name('pos.price');
        Route::post('pos', [PosController::class, 'store'])->name('pos.store');
    });

    // 1. PRODUCT & MANUFACTURING (manage products)
    Route::middleware('permission:manage products')->group(function () {
        Route::delete('produk/bulk-destroy', [ProdukController::class, 'bulkDestroy'])->name('produk.bulk-destroy');
        Route::resource('produk', ProdukController::class)->names('produk');

        Route::delete('satuan/bulk-destroy', [SatuanController::class, 'bulkDestroy'])->name('satuan.bulk-destroy');
        Route::resource('satuan', SatuanController::class);
        Route::post('satuan/quick', QuickCreateSatuanController::class)->name('satuan.quick');

        Route::delete('bom/bulk-destroy', [BOMController::class, 'bulkDestroy'])->name('bom.bulk-destroy');
        Route::resource('bom', BOMController::class);

        Route::delete('production/bulk-destroy', [ProductionController::class, 'bulkDestroy'])->name('production.bulk-destroy');
        Route::resource('production', ProductionController::class);
    });

    // 2. VENDOR & CUSTOMER MANAGEMENT
    Route::middleware('permission:manage vendors')->group(function () {
        Route::delete('vendors/bulk-destroy', [VendorController::class, 'bulkDestroy'])->name('vendor.bulk-destroy');
        Route::resource('vendors', VendorController::class)->names('vendor');
        Route::post('vendor/quick', QuickCreateVendorController::class)->name('vendor.quick');
    });

    Route::middleware('permission:manage customers')->group(function () {
        Route::delete('customers/bulk-destroy', [CustomerController::class, 'bulkDestroy'])->name('customer.bulk-destroy');
        Route::resource('customers', CustomerController::class)->names('customer');

        // Customer Prices
        Route::get('customer-prices', [CustomerPriceController::class, 'listAll'])->name('customer.prices.all');
        Route::get('customers/{customer}/prices', [CustomerPriceController::class, 'index'])->name('customer.prices.index');
        Route::post('customers/{customer}/prices', [CustomerPriceController::class, 'store'])->name('customer.prices.store');
        Route::put('customers/{customer}/prices/{price}', [CustomerPriceController::class, 'update'])->name('customer.prices.update');
        Route::delete('customers/{customer}/prices/{price}', [CustomerPriceController::class, 'destroy'])->name('customer.prices.destroy');
    });

    // 3. PROCUREMENT & STOCK (manage stock)
    Route::middleware('permission:manage stock')->group(function () {
        Route::delete('restock/bulk-destroy', [RestockController::class, 'bulkDestroy'])->name('restock.bulk-destroy');
        Route::resource('restock', RestockController::class);
        Route::post('restock/{restock}/settle', [RestockController::class, 'settle'])->name('restock.settle');

        // Purchasing (Formal Procurement)
        Route::resource('purchasing', PurchaseController::class)->parameters(['purchasing' => 'purchase']);
        Route::post('purchasing/{purchase}/finalize', [PurchaseController::class, 'finalize'])->name('purchasing.finalize');
        Route::delete('purchasing-attachment/{purchaseAttachment}', [PurchaseController::class, 'destroyAttachment'])->name('purchasing.attachment.destroy');

        // Stocks
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/{produk}', [StockController::class, 'show'])->name('stock.show');
        Route::post('stock/export-pdf', [StockController::class, 'exportMutationPdf'])->name('stock.export-pdf');
        Route::post('stock/adjustment', [StockController::class, 'adjustment'])->name('stock.adjustment');
        Route::resource('stock-opname', StockOpnameController::class);
        Route::post('stock-opname/{stock_opname}/storno', [StockOpnameController::class, 'storno'])->name('stock-opname.storno');
        Route::post('stock-opname/{stock_opname}/reopen', [StockOpnameController::class, 'reopen'])->name('stock-opname.reopen');
    });

    // 4. FINANCIALS & EXPENSES (view reports)
    Route::middleware('permission:view reports')->group(function () {
        Route::get('journal', [JournalController::class, 'index'])->name('journal.index');
        Route::get('profit-loss', [ProfitLossController::class, 'index'])->name('profit-loss.index');
        
        Route::delete('pengeluaran/bulk-destroy', [PengeluaranController::class, 'bulkDestroy'])->name('pengeluaran.bulk-destroy');
        Route::resource('pengeluaran', PengeluaranController::class);
    });

    // 5. SALES MANAGEMENT (void sales)
    Route::middleware('permission:void sales')->group(function () {
        Route::get('sales', [SalesController::class, 'index'])->name('sales.index');
        Route::get('sales/{sale}', [SalesController::class, 'show'])->name('sales.show');
        Route::post('sales/{sale}/void', [SalesController::class, 'void'])->name('sales.void');
    });

    // 6. PAYABLES & RECEIVABLES
    Route::middleware('permission:view payables')->group(function () {
        Route::get('payables', [PayableController::class, 'index'])->name('payables.index');
        Route::get('payables/{payable}', [PayableController::class, 'show'])->name('payables.show');

        // Manage Payments (Sub-permission)
        Route::middleware('permission:manage payments')->group(function () {
            Route::post('payables/{payable}/payments', [PayableController::class, 'storePayment'])->name('payables.payments.store');
        });
    });
});

require __DIR__.'/settings.php';
