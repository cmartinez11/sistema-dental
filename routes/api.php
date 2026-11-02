<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\DentistController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TreatmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas públicas de autenticación
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas que requieren Token de Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // 1. Módulo de Citas Odontológicas
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments/check-availability', [AppointmentController::class, 'checkAvailability']);

    // 2. Módulo de Pacientes
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);

    // 3. Catálogos Clínicos (Especialistas y Tratamientos)
    Route::get('/dentists', [DentistController::class, 'index']);
    Route::get('/treatments', [TreatmentController::class, 'index']);

    // 4. Módulo de Caja Diaria y Cobranzas
    Route::get('/cash-registers/current', [CashRegisterController::class, 'current']);
    Route::post('/cash-registers/open', [CashRegisterController::class, 'open']);
    Route::post('/cash-registers/{cashRegister}/close', [CashRegisterController::class, 'close']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/patients/{id}/installments', [TransactionController::class, 'getPatientInstallments']);

    // 5. Módulo de Historias Clínicas y Evolución
    Route::get('/medical-records', [MedicalRecordController::class, 'index']);
    Route::post('/medical-records', [MedicalRecordController::class, 'store']);
});

// Emisión y descarga de receta médica oficial en PDF
Route::get('/medical-records/{id}/pdf', [MedicalRecordController::class, 'downloadPdf']);
