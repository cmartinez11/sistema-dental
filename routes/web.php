<?php

use App\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

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
