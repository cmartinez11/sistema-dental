<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Models\CashRegister;
use App\Models\PaymentInstallment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionController extends Controller
{
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
                'message' => 'No se pueden registrar transacciones en una caja que se encuentra cerrada.'
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
                if (!empty($validated['installment_id'])) {
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
                'data' => $transaction->load(['patient', 'installment', 'cashRegister'])
            ], 201);

        } catch (Exception $e) {
            report($e);

            return response()->json([
                'message' => 'Ocurrió un error al procesar el pago en el sistema.'
            ], 500);
        }
    }
}
