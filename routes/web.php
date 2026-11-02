<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rutas públicas de autenticación Web
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas Web protegidas exclusivamente con el middleware de sesión ('auth')
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/dashboard/patients', [PatientController::class, 'indexView'])->name('patients.index');
    
    Route::get('/dashboard/appointments', function () {
        return view('appointments.index');
    })->name('appointments.index');

    Route::get('/dashboard/cash', function () {
        return view('cash.index');
    })->name('cash.index');

    Route::get('/dashboard/medical-records', function () {
        return view('medical-records.index');
    })->name('medical-records.index');

    Route::get('/appointments/check-availability', [AppointmentController::class, 'checkAvailability'])->name('appointments.check-availability');
    Route::resource('appointments', AppointmentController::class)->except(['index']);

    // Emisión y descarga de receta médica oficial en PDF (Vista web para impresión)
    Route::get('/medical-records/{id}/pdf', [MedicalRecordController::class, 'downloadPdf'])->name('medical-records.pdf');
});
