<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Determina si el usuario puede listar citas.
     * Admin y Recepcionista pueden ver todas; Dentista solo sus propias citas.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin)
            || $user->hasRole(UserRole::Recepcionista)
            || $user->hasRole(UserRole::Dentista);
    }

    /**
     * Determina si el usuario puede ver los detalles de una cita específica.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::Recepcionista)) {
            return true;
        }

        // Un dentista solo puede inspeccionar las citas que tiene asignadas
        if ($user->hasRole(UserRole::Dentista)) {
            return $appointment->dentist?->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determina si el usuario puede agendar una nueva cita.
     * Solo Recepcionistas y Administradores tienen acceso a la gestión de citas en mostrador.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::Recepcionista);
    }

    /**
     * Determina si el usuario puede reprogramar o modificar la cita.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::Recepcionista);
    }

    /**
     * Determina si el usuario puede cancelar la cita.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::Recepcionista);
    }
}

