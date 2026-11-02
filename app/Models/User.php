<?php

namespace App\Models;

use App\Enums\UserRole;
use Laravel\Sanctum\HasApiTokens;
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
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

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
     * Comprueba si el usuario tiene uno o más roles específicos.
     * Soporta enums UserRole o cadenas ('admin', 'dentist', 'receptionist', 'recepcionista', etc.)
     */
    public function hasRole(UserRole|string ...$roles): bool
    {
        $currentRole = $this->role instanceof UserRole ? $this->role->value : (string) $this->role;

        foreach ($roles as $role) {
            if ($role instanceof UserRole && $role === $this->role) {
                return true;
            }

            if (is_string($role)) {
                $check = strtolower(trim($role));
                if ($check === $currentRole) {
                    return true;
                }

                $matches = match ($check) {
                    'admin', 'administrador' => $currentRole === 'admin',
                    'dentist', 'dentista' => $currentRole === 'dentista',
                    'receptionist', 'recepcionista' => $currentRole === 'recepcionista',
                    default => false,
                };

                if ($matches) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determina si el usuario es Administrador.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Determina si el usuario es Recepcionista.
     */
    public function isReceptionist(): bool
    {
        return $this->hasRole('receptionist');
    }

    /**
     * Determina si el usuario es Dentista / Especialista.
     */
    public function isDentist(): bool
    {
        return $this->hasRole('dentist');
    }
}
