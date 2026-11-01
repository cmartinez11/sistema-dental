<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cash_register_id',
    'patient_id',
    'installment_id',
    'amount',
    'payment_method',
    'reference_code',
])]
class Transaction extends Model
{
    use HasFactory;

    /**
     * Castings de atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Sesión o turno de caja donde se recaudó el pago.
     */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    /**
     * Paciente emisor del pago.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Cuota de tratamiento asociada (nullable en caso de servicios directos).
     */
    public function installment(): BelongsTo
    {
        return $this->belongsTo(PaymentInstallment::class, 'installment_id');
    }
}

