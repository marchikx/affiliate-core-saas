<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Installer (unprotected — locks itself after completion)
|--------------------------------------------------------------------------
*/

Route::middleware('install.lock')
    ->prefix('install')
    ->group(function (): void {
        Route::get('/',      [InstallController::class, 'showInstaller']);
        Route::post('/step1', [InstallController::class, 'step1_database']);
        Route::post('/step2', [InstallController::class, 'step2_migrate']);
        Route::post('/step3', [InstallController::class, 'step3_seed']);
        Route::post('/step4', [InstallController::class, 'step4_admin']);
    });

/*
|--------------------------------------------------------------------------
| Authenticated SPA Routes (Inertia)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});
