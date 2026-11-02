<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\DentistSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    /**
     * Lista citas odontológicas aplicando filtros por fecha, odontólogo, sillón y RBAC.
     */
    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('viewAny', Appointment::class);

        $user = $request->user();

        $query = Appointment::with([
            'patient:id,first_name,last_name,dni,phone',
            'dentist.user:id,name',
            'treatment:id,name,base_price,estimated_duration_minutes',
        ]);

        // Restricción RBAC: Dentista solo puede visualizar las citas bajo su propia atención
        if ($user !== null && $user->hasRole(UserRole::Dentista)) {
            $dentistProfile = $user->dentist;
            if ($dentistProfile === null) {
                return $request->wantsJson()
                    ? response()->json(['data' => []])
                    : view('appointments.index', ['appointments' => collect()]);
            }

            $query->where('dentist_id', $dentistProfile->id);
        } elseif ($request->filled('dentist_id')) {
            $query->where('dentist_id', $request->integer('dentist_id'));
        }

        // Filtro por fecha específica (por defecto el día de hoy)
        $date = $request->input('date', Carbon::today()->toDateString());
        $query->whereDate('start_time', $date);

        // Filtros adicionales opcionales
        if ($request->filled('dental_chair')) {
            $query->where('dental_chair', $request->integer('dental_chair'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $appointments = $query->orderBy('start_time', 'asc')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'date' => $date,
                'count' => $appointments->count(),
                'data' => $appointments,
            ]);
        }

        return view('appointments.index', [
            'appointments' => $appointments,
            'selectedDate' => $date,
        ]);
    }

    /**
     * Registra una nueva cita garantizando la exclusión mutua de horarios tanto para
     * el cirujano dentista como para el sillón dental clínico mediante transacciones InnoDB.
     *
     * @throws ValidationException
     */
    public function store(StoreAppointmentRequest $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', Appointment::class);

        $validated = $request->validated();
        $startTime = Carbon::parse($validated['start_time']);
        $endTime = Carbon::parse($validated['end_time']);
        $dentistId = (int) $validated['dentist_id'];
        $chairNumber = (int) $validated['dental_chair'];
        $patientId = (int) $validated['patient_id'];

        // Ejecución atómica bajo transacción InnoDB con bloqueo pesimista
        $appointment = DB::transaction(function () use (
            $validated,
            $startTime,
            $endTime,
            $dentistId,
            $chairNumber,
            $patientId
        ): Appointment {
            // Bloqueo pesimista del registro del odontólogo para serializar agendamientos concurrentes
            $dentist = Dentist::where('id', $dentistId)->lockForUpdate()->firstOrFail();
            $dentist->ensureDefaultSchedules();

            // 0. Verificación de Horario y Disponibilidad Laboral del Odontólogo
            $dayOfWeek = $startTime->dayOfWeekIso; // 1 = Lunes, ..., 7 = Domingo
            $schedule = $dentist->schedules()->where('day_of_week', $dayOfWeek)->first();

            if (! $schedule || ! $schedule->is_active) {
                $dayName = DentistSchedule::getDayName($dayOfWeek);
                throw ValidationException::withMessages([
                    'start_time' => "El odontólogo no atiende citas los días {$dayName}.",
                ]);
            }

            if (! $schedule->isWithinShift($startTime, $endTime)) {
                $shiftStart = substr($schedule->start_time, 0, 5);
                $shiftEnd = substr($schedule->end_time, 0, 5);
                $appStart = $startTime->format('H:i');
                $appEnd = $endTime->format('H:i');
                throw ValidationException::withMessages([
                    'start_time' => "El horario seleccionado ({$appStart} - {$appEnd}) está fuera del turno laboral del odontólogo ({$shiftStart} - {$shiftEnd}).",
                ]);
            }

            // 1. Verificación de colisión de horario del Odontólogo
            $dentistHasConflict = Appointment::forDentistConflict(
                dentistId: $dentistId,
                startTime: $startTime,
                endTime: $endTime
            )->exists();

            if ($dentistHasConflict) {
                throw ValidationException::withMessages([
                    'dentist_id' => 'El odontólogo ya tiene una cita clínica programada en este horario.',
                ]);
            }

            // 2. Verificación de colisión de ocupación física del Sillón Odontológico
            $chairHasConflict = Appointment::forChairConflict(
                dentalChair: $chairNumber,
                startTime: $startTime,
                endTime: $endTime
            )->exists();

            if ($chairHasConflict) {
                throw ValidationException::withMessages([
                    'dental_chair' => "El sillón dental #{$chairNumber} ya se encuentra ocupado en este rango horario.",
                ]);
            }

            // 3. Verificación de doble agendamiento para el mismo paciente
            $patientHasConflict = Appointment::forPatientConflict(
                patientId: $patientId,
                startTime: $startTime,
                endTime: $endTime
            )->exists();

            if ($patientHasConflict) {
                throw ValidationException::withMessages([
                    'patient_id' => 'El paciente ya cuenta con otra cita registrada en este mismo intervalo horario.',
                ]);
            }

            // Inserción segura de la cita médica
            return Appointment::create([
                'patient_id' => $patientId,
                'dentist_id' => $dentistId,
                'treatment_id' => $validated['treatment_id'] ?? null,
                'dental_chair' => $chairNumber,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => AppointmentStatus::Scheduled,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Cita médica agendada exitosamente sin conflictos de horario.',
                'data' => $appointment->load(['patient', 'dentist.user', 'treatment']),
            ], 201);
        }

        return redirect()
            ->route('appointments.index', ['date' => $startTime->toDateString()])
            ->with('success', 'Cita agendada exitosamente en el sillón #' . $chairNumber);
    }

    /**
     * Muestra el detalle de una cita específica.
     */
    public function show(Request $request, Appointment $appointment): View|JsonResponse
    {
        Gate::authorize('view', $appointment);

        $appointment->load([
            'patient',
            'dentist.user',
            'treatment',
            'medicalRecord',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['data' => $appointment]);
        }

        return view('appointments.show', ['appointment' => $appointment]);
    }

    /**
     * Actualiza o reprograma una cita verificando ausencia de cruces con el nuevo intervalo.
     *
     * @throws ValidationException
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $appointment);

        $validated = $request->validated();

        $startTime = isset($validated['start_time'])
            ? Carbon::parse($validated['start_time'])
            : $appointment->start_time;

        $endTime = isset($validated['end_time'])
            ? Carbon::parse($validated['end_time'])
            : $appointment->end_time;

        $dentistId = isset($validated['dentist_id'])
            ? (int) $validated['dentist_id']
            : $appointment->dentist_id;

        $chairNumber = isset($validated['dental_chair'])
            ? (int) $validated['dental_chair']
            : $appointment->dental_chair;

        // Comprobación dentro de transacción si hay cambios que afecten agenda
        DB::transaction(function () use (
            $appointment,
            $validated,
            $startTime,
            $endTime,
            $dentistId,
            $chairNumber
        ): void {
            Dentist::where('id', $dentistId)->lockForUpdate()->firstOrFail();

            // Solo validar conflictos si la cita sigue activa
            $isBeingCancelled = isset($validated['status']) && $validated['status'] === AppointmentStatus::Cancelled->value;

            if (! $isBeingCancelled) {
                // Verificar conflicto del odontólogo excluyendo la cita actual
                $dentistConflict = Appointment::forDentistConflict(
                    dentistId: $dentistId,
                    startTime: $startTime,
                    endTime: $endTime,
                    ignoreAppointmentId: $appointment->id
                )->exists();

                if ($dentistConflict) {
                    throw ValidationException::withMessages([
                        'dentist_id' => 'El odontólogo ya tiene una cita asignada en ese nuevo horario.',
                    ]);
                }

                // Verificar conflicto del sillón excluyendo la cita actual
                $chairConflict = Appointment::forChairConflict(
                    dentalChair: $chairNumber,
                    startTime: $startTime,
                    endTime: $endTime,
                    ignoreAppointmentId: $appointment->id
                )->exists();

                if ($chairConflict) {
                    throw ValidationException::withMessages([
                        'dental_chair' => "El sillón dental #{$chairNumber} está ocupado en ese horario.",
                    ]);
                }
            }

            $appointment->update($validated);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Cita actualizada correctamente.',
                'data' => $appointment->fresh(['patient', 'dentist.user', 'treatment']),
            ]);
        }

        return redirect()
            ->route('appointments.index', ['date' => $appointment->start_time->toDateString()])
            ->with('success', 'Cita actualizada con éxito.');
    }

    /**
     * Cancela una cita médica liberando la disponibilidad del sillón y del especialista.
     */
    public function destroy(Request $request, Appointment $appointment): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $appointment);

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Cita cancelada con éxito. El horario y sillón han sido liberados.',
            ]);
        }

        return redirect()
            ->route('appointments.index')
            ->with('success', 'La cita fue cancelada y el horario quedó disponible.');
    }

    /**
     * Endpoint API para consultar disponibilidad en tiempo real de sillón y odontólogo.
     * Útil para interfaces interactivas de agenda / fullcalendar.
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'dentist_id' => ['required', 'integer', 'exists:dentists,id'],
            'dental_chair' => ['required', 'integer', 'min:1', 'max:10'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ]);

        $dentistId = $request->integer('dentist_id');
        $chairNumber = $request->integer('dental_chair');
        $startTime = Carbon::parse($request->input('start_time'));
        $endTime = Carbon::parse($request->input('end_time'));

        $dentist = Dentist::find($dentistId);
        $withinShift = false;
        $dayWorking = false;
        $shiftStartStr = '';
        $shiftEndStr = '';
        $scheduleMessage = '';

        if ($dentist) {
            $dentist->ensureDefaultSchedules();
            $dayOfWeek = $startTime->dayOfWeekIso;
            $schedule = $dentist->schedules()->where('day_of_week', $dayOfWeek)->first();

            if ($schedule && $schedule->is_active) {
                $dayWorking = true;
                $shiftStartStr = substr($schedule->start_time, 0, 5);
                $shiftEndStr = substr($schedule->end_time, 0, 5);
                $withinShift = $schedule->isWithinShift($startTime, $endTime);

                if (! $withinShift) {
                    $scheduleMessage = "El horario está fuera del turno laboral del especialista ({$shiftStartStr} - {$shiftEndStr}).";
                }
            } else {
                $dayName = DentistSchedule::getDayName($dayOfWeek);
                $scheduleMessage = "El especialista no atiende los días {$dayName}.";
            }
        }

        $dentistConflict = Appointment::forDentistConflict($dentistId, $startTime, $endTime)->exists();
        $chairConflict = Appointment::forChairConflict($chairNumber, $startTime, $endTime)->exists();

        $isAvailable = $dayWorking && $withinShift && ! $dentistConflict && ! $chairConflict;

        return response()->json([
            'is_available' => $isAvailable,
            'day_working' => $dayWorking,
            'within_shift' => $withinShift,
            'dentist_available' => ! $dentistConflict,
            'chair_available' => ! $chairConflict,
            'shift_start' => $shiftStartStr,
            'shift_end' => $shiftEndStr,
            'schedule_message' => $scheduleMessage,
            'dentist_id' => $dentistId,
            'dental_chair' => $chairNumber,
            'start_time' => $startTime->toIso8601String(),
            'end_time' => $endTime->toIso8601String(),
        ]);
    }
}

