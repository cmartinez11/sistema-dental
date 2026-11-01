<?php

namespace App\Enums;

enum InstallmentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';

    /**
     * Retorna la etiqueta legible del estado de la cuota.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Paid => 'Pagada',
            self::Overdue => 'Vencida',
        };
    }
}

