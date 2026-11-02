<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CashRegisterController extends Controller
{
    /**
     * Obtiene el estado de la caja del turno actual para el usuario autenticado o la clínica.
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        // Buscar caja abierta
        $cashRegister = CashRegister::where('status', 'open')
            ->when($user !== null && $user->isReceptionist(), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->latest('opened_at')
            ->first();

        if ($cashRegister === null) {
            return response()->json([
                'has_open_register' => false,
                'register' => null,
                'total_collected' => 0.00,
            ]);
        }

        // Calcular total recaudado en el turno actual
        $totalCollected = (float) Transaction::where('cash_register_id', $cashRegister->id)->sum('amount');

        return response()->json([
            'has_open_register' => true,
            'register' => $cashRegister->load('user:id,name'),
            'total_collected' => round($totalCollected, 2),
        ]);
    }

    /**
     * Realiza la apertura de caja para el turno de recepción.
     */
    public function open(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'opening_amount' => ['required', 'numeric', 'min:0', 'max:50000'],
        ]);

        // Verificar si ya existe una caja abierta para este usuario
        $existing = CashRegister::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existing !== null) {
            return response()->json([
                'message' => 'Ya cuenta con un turno de caja abierto.',
                'register' => $existing,
            ], 422);
        }

        $cashRegister = CashRegister::create([
            'user_id' => $user->id,
            'opening_amount' => $request->float('opening_amount'),
            'status' => 'open',
            'opened_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Turno de caja abierto exitosamente con S/. ' . number_format($cashRegister->opening_amount, 2),
            'register' => $cashRegister,
        ], 201);
    }

    /**
     * Cierra el turno de caja (Arqueo de caja al final de jornada).
     */
    public function close(Request $request, CashRegister $cashRegister): JsonResponse
    {
        Gate::authorize('update', $cashRegister);

        if ($cashRegister->status !== 'open') {
            return response()->json([
                'message' => 'Esta caja ya se encuentra cerrada.',
            ], 422);
        }

        $request->validate([
            'closing_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $cashRegister->update([
            'closing_amount' => $request->float('closing_amount'),
            'status' => 'closed',
            'closed_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Turno de caja cerrado exitosamente.',
            'register' => $cashRegister,
        ]);
    }
}

