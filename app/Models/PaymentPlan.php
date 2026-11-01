<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'patient_id',
    'dentist_id',
    'total_amount',
    'discount',
    'final_amount',
    'status',
])]
class PaymentPlan extends Model
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
            'total_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'final_amount' => 'decimal:2',
        ];
    }

    /**
     * Paciente titular del plan de tratamiento o presupuesto.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Odontólogo / Especialista tratante responsable del plan.
     */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class);
    }

    /**
     * Cuotas fraccionadas generadas para el plan (ej. cuotas de ortodoncia).
     */
    public function installments(): HasMany
    {
        return $this->hasMany(PaymentInstallment::class);
    }
}

