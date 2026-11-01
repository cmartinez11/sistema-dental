<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Patient;
use App\Models\Dentist;
use App\Models\CashRegister;
use App\Models\PaymentPlan;
use App\Models\PaymentInstallment;
use App\Enums\UserRole;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_register_payment_and_update_installment()
    {
        // 1. Crear usuario recepcionista
        $user = User::create([
            'name' => 'Recepcionista Caja',
            'email' => 'caja@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::Recepcionista
        ]);

        // 2. Crear paciente y dentista
        $patient = Patient::create([
            'first_name' => 'Carlos',
            'last_name' => 'Mendoza',
            'dni' => '45678912',
        ]);

        $userDentist = User::create([
            'name' => 'Dr. Ortiz',
            'email' => 'ortiz@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::Dentista
        ]);

        $dentist = Dentist::create([
            'user_id' => $userDentist->id,
            'cmp_number' => 'CMP99999',
            'specialty' => 'Ortodoncia',
        ]);

        // 3. Crear una caja abierta vinculada al usuario
        $cashRegister = CashRegister::create([
            'user_id' => $user->id,
            'opening_amount' => 100.00,
            'status' => 'open',
        ]);

        // 4. Crear plan de pago y una cuota pendiente
        $paymentPlan = PaymentPlan::create([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'total_amount' => 1000.00,
            'final_amount' => 1000.00,
            'status' => 'in_progress',
        ]);

        $installment = PaymentInstallment::create([
            'payment_plan_id' => $paymentPlan->id,
            'installment_number' => 1,
            'amount' => 150.00,
            'due_date' => now()->addDays(5),
            'status' => 'pending',
        ]);

        // 5. Simular la petición HTTP POST para pagar la cuota (ej. vía Yape/Plin)
        $response = $this->actingAs($user)->postJson('/api/transactions', [
            'cash_register_id' => $cashRegister->id,
            'patient_id' => $patient->id,
            'installment_id' => $installment->id,
            'amount' => 150.00,
            'payment_method' => 'yape_plin',
            'reference_code' => 'OP-987654321',
        ]);

        // 6. Validar respuesta exitosa (201)
        $response->assertStatus(201);

        // 7. Verificar transaccionalmente que la cuota cambió a estado 'paid'
        $this->assertDatabaseHas('payment_installments', [
            'id' => $installment->id,
            'status' => 'paid',
        ]);

        // 8. Verificar que la transacción quedó registrada en caja
        $this->assertDatabaseHas('transactions', [
            'cash_register_id' => $cashRegister->id,
            'patient_id' => $patient->id,
            'installment_id' => $installment->id,
            'amount' => 150.00,
            'payment_method' => 'yape_plin',
        ]);
    }
}
