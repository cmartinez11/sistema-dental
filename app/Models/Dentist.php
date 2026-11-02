<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'cmp_number',
    'specialty',
    'commission_percentage',
])]
class Dentist extends Model
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
            'commission_percentage' => 'decimal:2',
        ];
    }

    /**
     * Cuenta de usuario autenticable vinculada al especialista.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Citas programadas asignadas al odontólogo.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Registros clínicos emitidos por este odontólogo.
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Tratamientos presupuestados y planes asignados al especialista.
     */
    public function paymentPlans(): HasMany
    {
        return $this->hasMany(PaymentPlan::class);
    }

    /**
     * Horarios y turnos de disponibilidad laboral del dentista.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(DentistSchedule::class);
    }

    /**
     * Asegura y genera los turnos base para la semana si el odontólogo no los tiene inicializados.
     */
    public function ensureDefaultSchedules(): void
    {
        if ($this->schedules()->exists()) {
            return;
        }

        for ($day = 1; $day <= 7; $day++) {
            $this->schedules()->create([
                'day_of_week' => $day,
                'start_time' => '08:00:00',
                'end_time' => $day === 6 ? '15:00:00' : ($day === 7 ? '13:00:00' : '20:00:00'),
                'is_active' => $day !== 7, // Domingo inactivo por defecto
            ]);
        }
    }
}

