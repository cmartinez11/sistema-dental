<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Recepcionista = 'recepcionista';
    case Dentista = 'dentista';

    /**
     * Retorna la etiqueta legible del rol.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Recepcionista => 'Recepcionista',
            self::Dentista => 'Cirujano Dentista / Especialista',
        };
    }
}

