<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'opening_amount',
    'closing_amount',
    'status',
    'opened_at',
    'closed_at',
])]
class CashRegister extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * Castings de atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_amount' => 'decimal:2',
            'closing_amount' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Usuario (recepcionista o cajero) responsable de la apertura de caja.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Transacciones cobradas durante este turno de caja.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}

