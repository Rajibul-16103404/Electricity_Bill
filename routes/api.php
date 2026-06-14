<?php

use App\Http\Controllers\ConsumerIdController;
use Illuminate\Support\Facades\Route;

Route::apiResource('consumer-ids', ConsumerIdController::class)->except(['update']);
