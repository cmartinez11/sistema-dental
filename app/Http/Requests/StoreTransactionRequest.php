<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        // Solo la recepcionista o el admin pueden registrar pagos y transacciones en caja
        return $user && ($user->isReceptionist() || $user->isAdmin());
    }

    public function rules(): array
    {
        return [
            'cash_register_id' => ['required', 'exists:cash_registers,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'installment_id' => ['nullable', 'exists:payment_installments,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'payment_method' => ['required', 'in:cash,card,yape_plin,transfer'],
            'reference_code' => ['nullable', 'string', 'max:100'], // Número de operación o voucher
        ];
    }
}