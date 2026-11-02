<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'dentist_id',
    'day_of_week',
    'start_time',
    'end_time',
    'is_active',
])]
class DentistSchedule extends Model
{
    use HasFactory;

    /**
     * Mapeo de nombres legibles para los días de la semana (ISO-8601).
     */
    public const DAYS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Especialista odontológico al que pertenece este horario.
     */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class);
    }

    /**
     * Obtiene el nombre en español del día de la semana.
     */
    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? 'Desconocido';
    }

    /**
     * Retorna el nombre en español dado el número de día ISO (1 = Lunes, ..., 7 = Domingo).
     */
    public static function getDayName(int $day): string
    {
        return self::DAYS[$day] ?? 'Desconocido';
    }

    /**
     * Verifica si un intervalo de cita (inicio y fin) se encuentra completamente dentro de este turno laboral.
     */
    public function isWithinShift(Carbon|string $startTime, Carbon|string $endTime): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $appStart = is_string($startTime) ? Carbon::parse($startTime)->format('H:i:s') : $startTime->format('H:i:s');
        $appEnd = is_string($endTime) ? Carbon::parse($endTime)->format('H:i:s') : $endTime->format('H:i:s');

        $shiftStart = Carbon::parse($this->start_time)->format('H:i:s');
        $shiftEnd = Carbon::parse($this->end_time)->format('H:i:s');

        return $appStart >= $shiftStart && $appEnd <= $shiftEnd;
    }
}

