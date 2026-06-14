<?php

use App\Http\Controllers\ConsumerIdController;
use Illuminate\Support\Facades\Route;

Route::post('login', [ConsumerIdController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('consumer-ids/{consumer_id}/sync', [ConsumerIdController::class, 'sync']);
    Route::apiResource('consumer-ids', ConsumerIdController::class)->except(['update']);
});
