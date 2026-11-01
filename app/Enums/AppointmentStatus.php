<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Retorna la etiqueta legible del estado de la cita.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendada',
            self::Confirmed => 'Confirmada',
            self::Completed => 'Atendida',
            self::Cancelled => 'Cancelada',
        };
    }
}

