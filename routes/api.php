<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AffiliateLinkController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\PayoutController;
use App\Http\Controllers\Api\TrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tracking Pixel Routes — Zero-Middleware, Ultra-Lightweight
|--------------------------------------------------------------------------
*/

Route::prefix('pixel')
    ->withoutMiddleware(['*'])
    ->group(function (): void {
        Route::post('/ping',    [TrackingController::class, 'ping']);
        Route::post('/convert', [TrackingController::class, 'convert']);
    });

/*
|--------------------------------------------------------------------------
| Authenticated API — Sanctum SPA Guard
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function (): void {

    // ── Campaigns (Tenant Admin) ────────────────────────────
    Route::apiResource('campaigns', CampaignController::class);

    // ── Affiliate Links (Affiliates) ────────────────────────
    Route::get('links',          [AffiliateLinkController::class, 'index']);
    Route::post('links',         [AffiliateLinkController::class, 'store']);
    Route::delete('links/{id}',  [AffiliateLinkController::class, 'destroy']);

    // ── Payouts (Financial Core) ────────────────────────────
    Route::get('payouts',              [PayoutController::class, 'index']);
    Route::post('payouts/request',     [PayoutController::class, 'requestPayout']);
    Route::post('payouts/export-csv',  [PayoutController::class, 'exportMassPayouts']);
    Route::post('payouts/{id}/reject', [PayoutController::class, 'reject']);
});
