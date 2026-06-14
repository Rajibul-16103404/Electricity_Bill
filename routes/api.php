<?php

use App\Http\Controllers\ConsumerIdController;
use Illuminate\Support\Facades\Route;

Route::post('login', [ConsumerIdController::class, 'login']);
Route::post('check-token', [ConsumerIdController::class, 'checkToken']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('consumer-ids/{consumer_id}/sync', [ConsumerIdController::class, 'sync']);
    Route::get('consumer-ids/{consumer_id}/recharges', [ConsumerIdController::class, 'recharges']);
    Route::get('consumer-ids/{consumer_id}/monthly-usages', [ConsumerIdController::class, 'monthlyUsages']);
    Route::get('consumer-ids/{consumer_id}/daily-reports', [ConsumerIdController::class, 'dailyReports']);
    Route::post('consumer-ids/{consumer_id}/daily-reports', [ConsumerIdController::class, 'storeDailyReport']);
    Route::apiResource('consumer-ids', ConsumerIdController::class)->except(['update']);
});
