<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'patient_id',
    'dentist_id',
    'treatment_id',
    'dental_chair',
    'start_time',
    'end_time',
    'status',
    'notes',
])]
class Appointment extends Model
{
    use HasFactory;

    /**
     * Castings de atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'status' => AppointmentStatus::class,
            'dental_chair' => 'integer',
        ];
    }

    /**
     * Paciente citado.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Odontólogo / Especialista asignado.
     */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class);
    }

    /**
     * Tratamiento o servicio programado.
     */
    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    /**
     * Registro de evolución clínica o ficha médica asociada a la cita.
     */
    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    /**
     * Scope para excluir citas canceladas (las citas canceladas liberan el horario/sillón).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', AppointmentStatus::Cancelled);
    }

    /**
     * Scope para detectar solapamiento de horario con una ventana de tiempo dada.
     * Dos intervalos [A_start, A_end] y [B_start, B_end] se solapan si y solo si:
     * A_start < B_end Y A_end > B_start.
     */
    public function scopeOverlapping(
        Builder $query,
        CarbonInterface|string $startTime,
        CarbonInterface|string $endTime
    ): Builder {
        return $query->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);
    }

    /**
     * Scope para verificar conflicto con la agenda de un odontólogo específico.
     */
    public function scopeForDentistConflict(
        Builder $query,
        int $dentistId,
        CarbonInterface|string $startTime,
        CarbonInterface|string $endTime,
        ?int $ignoreAppointmentId = null
    ): Builder {
        return $query->active()
            ->where('dentist_id', $dentistId)
            ->overlapping($startTime, $endTime)
            ->when($ignoreAppointmentId !== null, fn (Builder $q) => $q->where('id', '!=', $ignoreAppointmentId));
    }

    /**
     * Scope para verificar conflicto con el sillón dental específico.
     */
    public function scopeForChairConflict(
        Builder $query,
        int $dentalChair,
        CarbonInterface|string $startTime,
        CarbonInterface|string $endTime,
        ?int $ignoreAppointmentId = null
    ): Builder {
        return $query->active()
            ->where('dental_chair', $dentalChair)
            ->overlapping($startTime, $endTime)
            ->when($ignoreAppointmentId !== null, fn (Builder $q) => $q->where('id', '!=', $ignoreAppointmentId));
    }

    /**
     * Scope para verificar conflicto con la disponibilidad del paciente (evitar doble cita simultánea).
     */
    public function scopeForPatientConflict(
        Builder $query,
        int $patientId,
        CarbonInterface|string $startTime,
        CarbonInterface|string $endTime,
        ?int $ignoreAppointmentId = null
    ): Builder {
        return $query->active()
            ->where('patient_id', $patientId)
            ->overlapping($startTime, $endTime)
            ->when($ignoreAppointmentId !== null, fn (Builder $q) => $q->where('id', '!=', $ignoreAppointmentId));
    }
}

