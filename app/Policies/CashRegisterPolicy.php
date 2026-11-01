<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CashRegister;
use App\Models\User;

class CashRegisterPolicy
{
    /**
     * Determina si el usuario puede consultar turnos de caja y finanzas.
     * Dentistas NO pueden ver reportes financieros globales ni arqueos de caja.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::Recepcionista);
    }

    /**
     * Determina si el usuario puede abrir o alterar una caja de recepción.
     * Solo la recepcionista titular del turno o el administrador.
     */
    public function update(User $user, CashRegister $cashRegister): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::Recepcionista)) {
            return $cashRegister->user_id === $user->id && $cashRegister->status === 'open';
        }

        // Dentistas tienen prohibido alterar transacciones o cajas
        return false;
    }

    /**
     * Determina si el usuario puede acceder a reportes financieros de la clínica.
     */
    public function viewFinancialReports(User $user): bool
    {
        return $user->hasRole(UserRole::Admin);
    }
}

