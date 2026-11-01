<?php

use App\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientController;

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::middleware(['auth'])->group(function () {
    Route::get('/appointments/check-availability', [AppointmentController::class, 'checkAvailability'])->name('appointments.check-availability');
    Route::resource('appointments', AppointmentController::class);
});


Route::get('/dashboard/appointments', function () {
    return view('appointments.index');
});

Route::get('/dashboard/cash', function () {
    return view('cash.index');
});

Route::get('/dashboard/medical-records', function () {
    return view('medical-records.index');
});

Route::get('/dashboard/patients', [PatientController::class, 'indexView']);