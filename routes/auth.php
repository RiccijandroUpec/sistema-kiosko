<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PinLoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Solo login de admin - sin registro de usuarios
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Acceso rapido de administrador por PIN (alternativa a email/password)
    Route::get('login-pin', [PinLoginController::class, 'showForm'])->name('login.pin.form');
    Route::post('login-pin', [PinLoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.pin');
});

Route::middleware('auth')->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

