<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Muestra el panel principal de control (Dashboard) con métricas en tiempo real.
     */
    public function index(): View
    {
        $today = Carbon::today();

        $totalPatients = Patient::count();
        $todayAppointments = Appointment::whereDate('start_time', $today)->count();
        $totalDentists = Dentist::count();
        $todayIncome = (float) Transaction::whereDate('created_at', $today)->sum('amount');

        return view('dashboard', compact(
            'totalPatients',
            'todayAppointments',
            'totalDentists',
            'todayIncome'
        ));
    }
}
