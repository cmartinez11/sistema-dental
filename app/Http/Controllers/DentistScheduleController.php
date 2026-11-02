<?php

namespace App\Http\Controllers;

use App\Models\Dentist;
use App\Models\DentistSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DentistScheduleController extends Controller
{
    /**
     * Muestra la interfaz administrativa de gestión de horarios y turnos de odontólogos.
     */
    public function index(Request $request): View|JsonResponse
    {
        $dentists = Dentist::with(['user', 'schedules'])->get();

        // Asegurar que cada dentista tenga sus 7 turnos inicializados
        foreach ($dentists as $dentist) {
            $dentist->ensureDefaultSchedules();
        }

        $dentists->load('schedules');

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $dentists,
            ]);
        }

        return view('dentist-schedules.index', compact('dentists'));
    }

    /**
     * Obtiene los horarios y turnos de un odontólogo específico.
     */
    public function show(int $dentistId): JsonResponse
    {
        $dentist = Dentist::with('user')->findOrFail($dentistId);
        $dentist->ensureDefaultSchedules();

        $schedules = $dentist->schedules()->orderBy('day_of_week', 'asc')->get()->map(function ($sch) {
            return [
                'id' => $sch->id,
                'day_of_week' => $sch->day_of_week,
                'day_name' => $sch->day_name,
                'start_time' => substr($sch->start_time, 0, 5),
                'end_time' => substr($sch->end_time, 0, 5),
                'is_active' => (bool) $sch->is_active,
            ];
        });

        return response()->json([
            'dentist' => [
                'id' => $dentist->id,
                'name' => $dentist->user?->name ?? 'Dr. ' . $dentist->id,
                'cmp_number' => $dentist->cmp_number,
                'specialty' => $dentist->specialty,
            ],
            'schedules' => $schedules,
        ]);
    }

    /**
     * Actualiza masivamente los horarios semanales del especialista.
     */
    public function update(Request $request, int $dentistId): JsonResponse|RedirectResponse
    {
        $dentist = Dentist::findOrFail($dentistId);

        $validated = $request->validate([
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'schedules.*.start_time' => ['required'],
            'schedules.*.end_time' => ['required'],
            'schedules.*.is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($dentist, $validated) {
            foreach ($validated['schedules'] as $item) {
                $start = strlen($item['start_time']) === 5 ? $item['start_time'] . ':00' : $item['start_time'];
                $end = strlen($item['end_time']) === 5 ? $item['end_time'] . ':00' : $item['end_time'];

                DentistSchedule::updateOrCreate(
                    [
                        'dentist_id' => $dentist->id,
                        'day_of_week' => $item['day_of_week'],
                    ],
                    [
                        'start_time' => $start,
                        'end_time' => $end,
                        'is_active' => (bool) $item['is_active'],
                    ]
                );
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => '¡Horarios del especialista actualizados correctamente!',
                'schedules' => $dentist->schedules()->orderBy('day_of_week')->get(),
            ]);
        }

        return redirect()->route('dentist-schedules.index')
            ->with('success', 'Horarios laborales del especialista actualizados con éxito.');
    }
}

