<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Models\CashRegister;
use App\Models\PaymentInstallment;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Lista las transacciones recientes optimizando las consultas con eager loading para evitar N+1.
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 15);

        $transactions = Transaction::with([
            'patient:id,first_name,last_name,dni',
            'cashRegister:id,user_id,status',
            'installment:id,installment_number,amount,due_date,status',
        ])
            ->latest('id')
            ->take($limit)
            ->get();

        return response()->json([
            'count' => $transactions->count(),
            'data' => $transactions,
        ]);
    }

    /**
     * Obtiene las cuotas fraccionadas (Ortodoncia u otros planes) pendientes o vencidas de un paciente.
     */
    public function getPatientInstallments(int $patientId): JsonResponse
    {
        $installments = PaymentInstallment::with(['paymentPlan:id,patient_id,dentist_id,total_amount'])
            ->whereHas('paymentPlan', function ($query) use ($patientId) {
                $query->where('patient_id', $patientId);
            })
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->get();

        return response()->json([
            'patient_id' => $patientId,
            'count' => $installments->count(),
            'data' => $installments,
        ]);
    }

    /**
     * Registra un nuevo pago en la caja abierta y actualiza la cuota asociada.
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Verificar que la caja esté abierta
        $cashRegister = CashRegister::findOrFail($validated['cash_register_id']);
        if ($cashRegister->status !== 'open') {
            return response()->json([
                'message' => 'No se pueden registrar transacciones en una caja que se encuentra cerrada.',
            ], 422);
        }

        try {
            $transaction = DB::transaction(function () use ($validated) {
                // 1. Crear la transacción de ingreso
                $transaction = Transaction::create([
                    'cash_register_id' => $validated['cash_register_id'],
                    'patient_id' => $validated['patient_id'],
                    'installment_id' => $validated['installment_id'] ?? null,
                    'amount' => $validated['amount'],
                    'payment_method' => $validated['payment_method'],
                    'reference_code' => $validated['reference_code'] ?? null,
                ]);

                // 2. Si el pago está vinculado a una cuota de ortodoncia, marcarla como pagada
                if (! empty($validated['installment_id'])) {
                    $installment = PaymentInstallment::findOrFail($validated['installment_id']);
                    $installment->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                }

                return $transaction;
            });

            return response()->json([
                'message' => 'Pago registrado e ingresado a caja exitosamente.',
                'data' => $transaction->load(['patient', 'installment', 'cashRegister']),
            ], 201);

        } catch (Exception $e) {
            report($e);

            return response()->json([
                'message' => 'Ocurrió un error al procesar el pago en el sistema.',
            ], 500);
        }
    }
}
