<?php

use App\Http\Controllers\Api\V1\PhaseOneHealthController;
use App\Http\Controllers\Api\V1\UkBankHolidayController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/phase1/health', PhaseOneHealthController::class)->name('api.v1.phase1.health');
    Route::get('/uk-bank-holidays', UkBankHolidayController::class)->name('api.v1.uk-bank-holidays');
});

