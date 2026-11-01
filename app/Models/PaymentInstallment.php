<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'payment_plan_id',
    'installment_number',
    'amount',
    'due_date',
    'status',
    'paid_at',
])]
class PaymentInstallment extends Model
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
            'installment_number' => 'integer',
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'status' => InstallmentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Plan de tratamiento o presupuesto de ortodoncia al que pertenece la cuota.
     */
    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    /**
     * Transacciones o recibos de pago emitidos para amortizar o liquidar esta cuota.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'installment_id');
    }

    /**
     * Paciente titular asociado mediante el plan de pago.
     */
    public function patient(): HasOneThrough
    {
        return $this->hasOneThrough(
            Patient::class,
            PaymentPlan::class,
            'id',             // Llave foránea en payment_plans referenciada por payment_installments.payment_plan_id
            'id',             // Llave primaria en patients
            'payment_plan_id',// Llave local en payment_installments
            'patient_id'      // Llave foránea en payment_plans que apunta a patients.id
        );
    }

    /**
     * Odontólogo tratante responsable del plan de tratamiento asociado.
     */
    public function dentist(): HasOneThrough
    {
        return $this->hasOneThrough(
            Dentist::class,
            PaymentPlan::class,
            'id',
            'id',
            'payment_plan_id',
            'dentist_id'
        );
    }

    /**
     * Scope para detectar cuotas vencidas (Overdue).
     *
     * Incluye tanto las cuotas marcadas explícitamente con estado 'overdue'
     * como aquellas con estado 'pending' cuya fecha de vencimiento sea anterior a la fecha actual.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();

        return $query->where(function (Builder $subQuery) use ($today): void {
            $subQuery->where('status', InstallmentStatus::Overdue)
                ->orWhere(function (Builder $pendingQuery) use ($today): void {
                    $pendingQuery->where('status', InstallmentStatus::Pending)
                        ->whereDate('due_date', '<', $today);
                });
        });
    }

    /**
     * Scope para cuotas pendientes no vencidas.
     */
    public function scopePending(Builder $query): Builder
    {
        $today = Carbon::today()->toDateString();

        return $query->where('status', InstallmentStatus::Pending)
            ->whereDate('due_date', '>=', $today);
    }

    /**
     * Scope para cuotas completamente liquidadas/pagadas.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', InstallmentStatus::Paid);
    }

    /**
     * Scope para cuotas con vencimiento dentro de un rango de fechas.
     */
    public function scopeDueBetween(
        Builder $query,
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate
    ): Builder {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    /**
     * Determina si la cuota se encuentra efectivamente vencida al momento de evaluarse.
     */
    public function isOverdue(): bool
    {
        if ($this->status === InstallmentStatus::Overdue) {
            return true;
        }

        return $this->status === InstallmentStatus::Pending
            && $this->due_date->isPast()
            && ! $this->due_date->isToday();
    }

    /**
     * Determina si la cuota está totalmente pagada.
     */
    public function isPaid(): bool
    {
        return $this->status === InstallmentStatus::Paid;
    }

    /**
     * Calcula los días de mora transcurridos respecto a la fecha actual.
     */
    public function daysOverdue(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return (int) $this->due_date->diffInDays(Carbon::today(), false);
    }

    /**
     * Registra de forma transaccional y atómica el pago de la cuota en caja.
     *
     * @param  array{
     *     cash_register_id: int,
     *     payment_method: string,
     *     reference_code?: string|null,
     * }  $paymentData
     */
    public function registerPayment(array $paymentData): Transaction
    {
        return DB::transaction(function () use ($paymentData): Transaction {
            $this->lockForUpdate();

            $this->status = InstallmentStatus::Paid;
            $this->paid_at = now();
            $this->save();

            $patientId = $this->paymentPlan->patient_id;

            return $this->transactions()->create([
                'cash_register_id' => $paymentData['cash_register_id'],
                'patient_id' => $patientId,
                'amount' => $this->amount,
                'payment_method' => $paymentData['payment_method'],
                'reference_code' => $paymentData['reference_code'] ?? null,
            ]);
        });
    }
}

