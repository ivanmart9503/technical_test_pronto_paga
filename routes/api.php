<?php

use App\Http\Controllers\Api\AppointmentsController;
use App\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/appointments', [AppointmentsController::class, 'index']);
    Route::post('/appointments', [AppointmentsController::class, 'create']);
    Route::put('/appointments/{appointment}/confirm', [AppointmentsController::class, 'confirm']);
});
