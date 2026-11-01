<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\TransactionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Registrar la ruta de citas protegida por Sanctum
Route::middleware('auth:sanctum')->post('/appointments', [AppointmentController::class, 'store']);

// Ruta protegida para registrar pagos y transacciones en caja
Route::middleware('auth:sanctum')->post('/transactions', [TransactionController::class, 'store']);