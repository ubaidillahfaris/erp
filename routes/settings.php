<?php

use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\Settings\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    // Management Routes (Admin Permissions)
    Route::middleware('permission:manage users')->group(function () {
        Route::resource('settings/users', UserController::class)->names('users');
    });

    Route::middleware('permission:manage roles')->group(function () {
        Route::resource('settings/roles', RoleController::class)->names('roles');
    });

    Route::middleware('permission:manage services')->group(function () {
        Route::get('settings/services', [ServiceController::class, 'index'])->name('settings.services.index');
        Route::get('settings/services/create', [ServiceController::class, 'create'])->name('settings.services.create');
        Route::post('settings/services', [ServiceController::class, 'store'])->name('settings.services.store');
        Route::get('settings/services/{service}', [ServiceController::class, 'show'])->name('settings.services.show');
        Route::post('settings/services/{service}/types', [ServiceController::class, 'storeType'])->name('settings.services.store-type');
        Route::post('settings/service-types/{serviceType}/pricings', [ServiceController::class, 'storePricing'])->name('settings.service-types.store-pricing');
        Route::put('settings/service-pricings/{pricing}', [ServiceController::class, 'updatePricing'])->name('settings.service-pricings.update');
        Route::delete('settings/service-pricings/{pricing}', [ServiceController::class, 'destroyPricing'])->name('settings.service-pricings.destroy');
        Route::post('settings/services/{service}/statuses', [ServiceController::class, 'syncStatuses'])->name('settings.services.sync-statuses');
        Route::delete('settings/service-categories/bulk-destroy', [ServiceCategoryController::class, 'bulkDestroy'])->name('settings.service-categories.bulk-destroy');
        Route::resource('settings/service-categories', ServiceCategoryController::class)->names('settings.service-categories');
    });
});
