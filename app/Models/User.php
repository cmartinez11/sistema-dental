<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'role', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Relación con el perfil de dentista si el usuario es especialista.
     */
    public function dentist(): HasOne
    {
        return $this->hasOne(Dentist::class);
    }

    /**
     * Comprueba si el usuario tiene un rol específico.
     */
    public function hasRole(UserRole|string $role): bool
    {
        if (is_string($role)) {
            $role = UserRole::tryFrom($role);
        }

        return $this->role === $role;
    }

    /**
     * Determina si el usuario es Administrador.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Determina si el usuario es Recepcionista.
     */
    public function isReceptionist(): bool
    {
        return $this->role === UserRole::Recepcionista;
    }

    /**
     * Determina si el usuario es Dentista / Especialista.
     */
    public function isDentist(): bool
    {
        return $this->role === UserRole::Dentista;
    }
}
