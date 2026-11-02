<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DentistScheduleController;
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

// Rutas Web protegidas con autenticación y control de acceso basado en roles (RBAC)
Route::middleware(['auth'])->group(function () {

    // 1. Panel General: Administrador (acceso total a métricas y reportes)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:admin')
        ->name('dashboard');

    // 2. Directorio de Pacientes: Admin, Recepcionista, Dentista
    Route::get('/dashboard/patients', [PatientController::class, 'indexView'])
        ->middleware('role:admin,receptionist,dentist')
        ->name('patients.index');

    // 3. Gestión de Citas y Agenda Clínica: Admin, Recepcionista, Dentista
    Route::get('/dashboard/appointments', function () {
        return view('appointments.index');
    })->middleware('role:admin,receptionist,dentist')->name('appointments.index');

    Route::get('/appointments/check-availability', [AppointmentController::class, 'checkAvailability'])
        ->middleware('role:admin,receptionist,dentist')
        ->name('appointments.check-availability');

    Route::resource('appointments', AppointmentController::class)
        ->middleware('role:admin,receptionist,dentist')
        ->except(['index']);

    // 4. Caja Diaria, Cobranzas y Arqueo: Admin y Recepcionista (Dentista bloqueado)
    Route::get('/dashboard/cash', function () {
        return view('cash.index');
    })->middleware('role:admin,receptionist')->name('cash.index');

    // 5. Historias Clínicas, Diagnóstico y Recetas: Admin y Dentista (Recepcionista bloqueada)
    Route::get('/dashboard/medical-records', function () {
        return view('medical-records.index');
    })->middleware('role:admin,dentist')->name('medical-records.index');

    // Emisión y descarga de receta médica oficial en PDF
    Route::get('/medical-records/{id}/pdf', [MedicalRecordController::class, 'downloadPdf'])
        ->middleware('role:admin,dentist')
        ->name('medical-records.pdf');

    // 6. Configuración de Horarios y Disponibilidad de Odontólogos: Solo Administrador
    Route::get('/dashboard/dentist-schedules', [DentistScheduleController::class, 'index'])
        ->middleware('role:admin')
        ->name('dentist-schedules.index');

    Route::get('/dashboard/dentist-schedules/{id}', [DentistScheduleController::class, 'show'])
        ->middleware('role:admin')
        ->name('dentist-schedules.show');

    Route::post('/dashboard/dentist-schedules/{id}', [DentistScheduleController::class, 'update'])
        ->middleware('role:admin')
        ->name('dentist-schedules.update');
});
