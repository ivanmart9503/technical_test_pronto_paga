<?php

use App\Http\Controllers\Api\AppointmentsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentsController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])
    ->name('auth.login');

Route::name('appointments.')->prefix('appointments')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [AppointmentsController::class, 'index'])
            ->name('index');
        Route::get('/today', [AppointmentsController::class, 'getAppointmentsForToday'])
            ->name('today');
        Route::get('/{appointment}/pay', [PaymentsController::class, 'pay'])
            ->name('pay');

        Route::post('/', [AppointmentsController::class, 'create'])
            ->name('create');

        Route::put('/{appointment}/confirm', [AppointmentsController::class, 'confirm'])
            ->name('confirm');

        Route::put('/{appointment}/cancel', [AppointmentsController::class, 'cancel'])
            ->name('cancel');
    });

    Route::middleware('guest')->group(function () {
        Route::get('/{appointment}/payment-success', [PaymentsController::class, 'paymentSuccess'])
            ->name('payment-success');
        Route::get('/{appointment}/payment-failed', [PaymentsController::class, 'paymentFailed'])
            ->name('payment-failed');
    });
});
