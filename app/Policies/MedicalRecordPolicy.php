<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MedicalRecord;
use App\Models\User;

class MedicalRecordPolicy
{
    /**
     * Determina si el usuario puede consultar registros médicos.
     * Solo los Dentistas tratantes y Administradores pueden acceder a historias clínicas.
     */
    public function view(User $user, MedicalRecord $record): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::Dentista)) {
            return $record->dentist?->user_id === $user->id;
        }

        // Recepcionistas NO tienen acceso al contenido confidencial de historias clínicas
        return false;
    }

    /**
     * Determina si el usuario puede crear una ficha de evolución o historia clínica.
     * Estrictamente reservado para Cirujanos Dentistas y Administradores clínicos.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::Dentista);
    }

    /**
     * Determina si el usuario puede modificar la historia clínica.
     * La recepcionista NUNCA puede modificar diagnósticos ni notas de evolución.
     */
    public function update(User $user, MedicalRecord $record): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        // El dentista solo puede editar sus propios registros médicos
        if ($user->hasRole(UserRole::Dentista)) {
            return $record->dentist?->user_id === $user->id;
        }

        return false;
    }

    /**
     * Eliminación de historias clínicas: reservado estrictamente para Administrador por auditoría legal.
     */
    public function delete(User $user, MedicalRecord $record): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}

