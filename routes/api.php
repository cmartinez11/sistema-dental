<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas públicas de autenticación
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas que requieren Token de Sanctum
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Registrar la ruta de citas protegida por Sanctum
Route::middleware('auth:sanctum')->post('/appointments', [AppointmentController::class, 'store']);

// Ruta protegida para registrar pagos y transacciones en caja
Route::middleware('auth:sanctum')->post('/transactions', [TransactionController::class, 'store']);

Route::middleware('auth:sanctum')->post('/medical-records', [MedicalRecordController::class, 'store']);


Route::get('/medical-records/{id}/pdf', [MedicalRecordController::class, 'downloadPdf']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
});