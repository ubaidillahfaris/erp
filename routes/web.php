<?php

use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\AgingReportController;
use App\Http\Controllers\Accounting\JournalController;
use App\Http\Controllers\Accounting\PeriodLockController;
use App\Http\Controllers\Accounting\TrialBalanceController;
use App\Http\Controllers\BOMController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPriceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\QuickCreateUnitController;
use App\Http\Controllers\QuickCreateVendorController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\System\AuditLogController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified', 'dynamic_menu'])->group(function () {
    // Accessible by anyone with 'view dashboard' permission
    Route::middleware('permission:view dashboard')->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 0. POINT OF SALE (make sales)
    Route::middleware(['permission:make sales', 'period_lock'])->group(function () {
        Route::get('pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('pos/price', [PosController::class, 'getPrice'])->name('pos.price');
        Route::post('pos', [PosController::class, 'store'])->name('pos.store');
    });

    // 1. PRODUCT & MANUFACTURING (manage products)
    Route::middleware('permission:manage products')->group(function () {
        Route::delete('product/bulk-destroy', [ProductController::class, 'bulkDestroy'])->name('product.bulk-destroy');
        Route::resource('product', ProductController::class)->names('product');

        Route::delete('unit/bulk-destroy', [UnitController::class, 'bulkDestroy'])->name('unit.bulk-destroy');
        Route::resource('unit', UnitController::class);
        Route::post('unit/quick', QuickCreateUnitController::class)->name('unit.quick');

        Route::delete('bom/bulk-destroy', [BOMController::class, 'bulkDestroy'])->name('bom.bulk-destroy');
        Route::resource('bom', BOMController::class);

        Route::middleware('period_lock')->group(function () {
            Route::delete('production/bulk-destroy', [ProductionController::class, 'bulkDestroy'])->name('production.bulk-destroy');
            Route::resource('production', ProductionController::class);
        });
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

        // Customer Credit & Discounts
        Route::post('customers/{customer}/credit-setting', [CustomerPriceController::class, 'storeCreditSetting'])->name('customer.credit-setting.store');
        Route::put('customers/{customer}/credit-setting', [CustomerPriceController::class, 'updateCreditSetting'])->name('customer.credit-setting.update');
        Route::post('customers/{customer}/category-discounts', [CustomerPriceController::class, 'storeCategoryDiscount'])->name('customer.category-discounts.store');
        Route::delete('customers/{customer}/category-discounts/{discount}', [CustomerPriceController::class, 'destroyCategoryDiscount'])->name('customer.category-discounts.destroy');
    });

    // 3. PROCUREMENT & STOCK (manage stock)
    Route::middleware(['permission:manage stock', 'period_lock'])->group(function () {
        Route::get('restock', [RestockController::class, 'index'])->name('restock.index');
        Route::delete('restock/bulk-destroy', [RestockController::class, 'bulkDestroy'])->name('restock.bulk-destroy');
        Route::resource('restock', RestockController::class)->except(['index']);
        Route::post('restock/{restock}/settle', [RestockController::class, 'settle'])->name('restock.settle');

        // Purchasing (Formal Procurement)
        Route::resource('purchasing', PurchaseController::class)->parameters(['purchasing' => 'purchase']);
        Route::post('purchasing/{purchase}/finalize', [PurchaseController::class, 'finalize'])->name('purchasing.finalize');
        Route::delete('purchasing-attachment/{purchaseAttachment}', [PurchaseController::class, 'destroyAttachment'])->name('purchasing.attachment.destroy');

        // Stocks
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/{product}', [StockController::class, 'show'])->name('stock.show');
        Route::post('stock/export-pdf', [StockController::class, 'exportMutationPdf'])->name('stock.export-pdf');
        Route::post('stock/adjustment', [StockController::class, 'adjustment'])->name('stock.adjustment');
        Route::resource('stock-opname', StockOpnameController::class);
        Route::post('stock-opname/{stock_opname}/storno', [StockOpnameController::class, 'stornoOpname'])->name('stock-opname.storno');
        Route::post('stock-opname/{stock_opname}/reopen', [StockOpnameController::class, 'reopen'])->name('stock-opname.reopen');

        // Warehouses
        Route::resource('warehouses', WarehouseController::class);

        // Stock Transfers
        Route::get('stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
        Route::get('stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
        Route::post('stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');
        Route::get('stock-transfers/{stock_transfer}', [StockTransferController::class, 'show'])->name('stock-transfers.show');
        Route::post('stock-transfers/{stock_transfer}/dispatch', [StockTransferController::class, 'dispatch'])->name('stock-transfers.dispatch');
        Route::post('stock-transfers/{stock_transfer}/receive', [StockTransferController::class, 'receive'])->name('stock-transfers.receive');
        Route::post('stock-transfers/{stock_transfer}/cancel', [StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');
    });

    // 4. FINANCIALS & EXPENSES (view reports)
    Route::middleware('permission:view reports')->group(function () {
        Route::get('journal', [JournalController::class, 'index'])
            ->name('journal.index');

        // Accounting Module
        Route::prefix('accounting')->group(function () {
            Route::get('journal', [JournalController::class, 'index'])->name('accounting.journal.index');
            Route::get('trial-balance', [TrialBalanceController::class, 'index'])->name('accounting.trial-balance.index');
            Route::post('accounting/trial-balance/refresh', [TrialBalanceController::class, 'refresh'])
                ->name('accounting.trial-balance.refresh');
            Route::get('aging', [AgingReportController::class, 'index'])->name('accounting.aging.index');
            Route::resource('accounts', AccountController::class);

            // Feature: Period Locks
            Route::resource('periods', PeriodLockController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->names('accounting.periods');
        });

        Route::get('profit-loss', [TrialBalanceController::class, 'index'])
            ->name('profit-loss.index');

        // Feature: System Audit Log
        Route::get('system/audit-log', [AuditLogController::class, 'index'])
            ->name('system.audit-log.index');

        Route::middleware('period_lock')->group(function () {
            Route::delete('pengeluaran/bulk-destroy', [PengeluaranController::class, 'bulkDestroy'])->name('pengeluaran.bulk-destroy');
            Route::resource('pengeluaran', PengeluaranController::class);
        });
    });

    // 5. SALES MANAGEMENT (void sales, returns)
    Route::middleware(['permission:void sales'])->group(function () {
        Route::get('sales', [SalesController::class, 'index'])->name('sales.index');
        Route::get('sales/{sale}', [SalesController::class, 'show'])->name('sales.show');

        Route::middleware('period_lock')->group(function () {
            Route::post('sales/{sale}/void', [SalesController::class, 'stornoSale'])->name('sales.void');
        });

        // Credit Notes (Partial Returns)
        Route::get('credit-notes', [CreditNoteController::class, 'index'])->name('credit-notes.index');
        Route::get('credit-notes/create', [CreditNoteController::class, 'createGeneral'])->name('credit-notes.create-general');
        Route::get('credit-notes/sale-details/{sale}', [CreditNoteController::class, 'getSaleDetails'])->name('credit-notes.sale-details');
        Route::get('credit-notes/{credit_note}', [CreditNoteController::class, 'show'])->name('credit-notes.show');
        Route::get('sales/{sale}/return', [CreditNoteController::class, 'create'])->name('credit-notes.create');

        Route::middleware('period_lock')->group(function () {
            Route::post('credit-notes', [CreditNoteController::class, 'store'])->name('credit-notes.store');
            Route::post('credit-notes/{credit_note}/post', [CreditNoteController::class, 'post'])->name('credit-notes.post');
        });
    });

    // 6. PAYABLES & RECEIVABLES
    Route::middleware('permission:view payables')->group(function () {
        Route::get('payables', [PayableController::class, 'index'])->name('payables.index');
        Route::get('payables/{payable}', [PayableController::class, 'show'])->name('payables.show');

        // Manage Payments (Sub-permission)
        Route::middleware(['permission:manage payments', 'period_lock'])->group(function () {
            Route::post('payables/{payable}/payments', [PayableController::class, 'storePayment'])->name('payables.payments.store');
        });
    });

    // Employee Management
    Route::resource('employees', EmployeeController::class);
});

require __DIR__.'/settings.php';
