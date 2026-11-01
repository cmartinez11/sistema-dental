<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'patient_id',
    'dentist_id',
    'appointment_id',
    'diagnosis',
    'evolution_notes',
    'prescription',
])]
class MedicalRecord extends Model
{
    use HasFactory;

    /**
     * Paciente titular de la historia clínica.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Especialista tratante que redactó las notas de evolución.
     */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class);
    }

    /**
     * Cita médica durante la cual se registró la evolución clínica (nullable).
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}

