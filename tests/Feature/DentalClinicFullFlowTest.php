<?php

namespace Tests\Feature;

use App\Enums\InstallmentStatus;
use App\Enums\UserRole;
use App\Models\CashRegister;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PaymentInstallment;
use App\Models\PaymentPlan;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DentalClinicFullFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_clinical_workflow_reception_cash_and_dental_chair(): void
    {
        // 1. Crear usuarios y perfil
        $receptionist = User::create([
            'name' => 'Lucía Recepción',
            'email' => 'recepcion@sjl.pe',
            'password' => bcrypt('password123'),
            'role' => UserRole::Recepcionista,
        ]);

        $dentistUser = User::create([
            'name' => 'Dr. Carlos Ruiz',
            'email' => 'dentista@sjl.pe',
            'password' => bcrypt('password123'),
            'role' => UserRole::Dentista,
        ]);

        $dentist = Dentist::create([
            'user_id' => $dentistUser->id,
            'cmp_number' => 'COP-77441',
            'specialty' => 'Ortodoncia',
        ]);

        $treatment = Treatment::create([
            'name' => 'Ortodoncia Metálica Mensual',
            'base_price' => 200.00,
            'estimated_duration_minutes' => 45,
        ]);

        $patient = Patient::create([
            'first_name' => 'Roberto',
            'last_name' => 'Paredes',
            'dni' => '72849102',
            'phone' => '987654321',
            'allergies' => 'Alérgico a Penicilina',
        ]);

        // 2. Autenticación con Sanctum para Recepcionista
        $loginRes = $this->postJson('/api/login', [
            'email' => 'recepcion@sjl.pe',
            'password' => 'password123',
        ]);
        $loginRes->assertStatus(200);
        $token = $loginRes->json('token');
        $this->assertNotEmpty($token);

        $authHeaders = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ];

        // 3. Recepción: Agendar cita en el Sillón #1
        $start = Carbon::tomorrow()->setHour(10)->setMinute(0);
        $end = $start->copy()->addMinutes(45);

        $aptRes = $this->withHeaders($authHeaders)->postJson('/api/appointments', [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'treatment_id' => $treatment->id,
            'dental_chair' => 1,
            'start_time' => $start->toDateTimeString(),
            'end_time' => $end->toDateTimeString(),
            'notes' => 'Control mensual de ortodoncia',
        ]);
        $aptRes->assertStatus(201);
        $appointmentId = $aptRes->json('data.id');

        // 4. Recepción: Apertura de Caja Diaria
        $openCashRes = $this->withHeaders($authHeaders)->postJson('/api/cash-registers/open', [
            'opening_amount' => 200.00,
        ]);
        $openCashRes->assertStatus(201);
        $cashRegisterId = $openCashRes->json('register.id');

        // 5. Generar Plan de Ortodoncia y Cuota Vencida
        $plan = PaymentPlan::create([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'total_amount' => 2400.00,
            'final_amount' => 2400.00,
        ]);

        $installment = PaymentInstallment::create([
            'payment_plan_id' => $plan->id,
            'installment_number' => 1,
            'amount' => 200.00,
            'due_date' => Carbon::now()->subDays(5)->toDateString(),
            'status' => InstallmentStatus::Overdue,
        ]);

        // 6. Consultar cuotas pendientes del paciente
        $installmentsRes = $this->withHeaders($authHeaders)->getJson("/api/patients/{$patient->id}/installments");
        $installmentsRes->assertStatus(200);
        $this->assertEquals(1, $installmentsRes->json('count'));

        // 7. Cobrar la cuota en Caja con Yape
        $payRes = $this->withHeaders($authHeaders)->postJson('/api/transactions', [
            'cash_register_id' => $cashRegisterId,
            'patient_id' => $patient->id,
            'installment_id' => $installment->id,
            'amount' => 200.00,
            'payment_method' => 'yape_plin',
            'reference_code' => 'YAPE-99887766',
        ]);
        $payRes->assertStatus(201);

        $this->assertEquals(InstallmentStatus::Paid, $installment->fresh()->status);
        $this->assertNotNull($installment->fresh()->paid_at);

        // 8. Consultorio: Dentista registra Ficha Clínica y Notas de Evolución
        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $dentistLogin = $this->postJson('/api/login', [
            'email' => 'dentista@sjl.pe',
            'password' => 'password123',
        ]);
        $this->app['auth']->forgetGuards();
        $dentistToken = $dentistLogin->json('token');
        $dentistHeaders = [
            'Authorization' => "Bearer {$dentistToken}",
            'Accept' => 'application/json',
        ];

        $recordRes = $this->withHeaders($dentistHeaders)->postJson('/api/medical-records', [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'appointment_id' => $appointmentId,
            'diagnosis' => 'Control de brackets y cambio de arcos superiores.',
            'evolution_notes' => 'Se colocó arco de níquel-titanio 0.016. Buena higiene.',
            'prescription' => 'Ketorolaco 10mg sublingual: solo en caso de dolor post-ajuste.',
        ]);
        $recordRes->assertStatus(201);
        $medicalRecordId = $recordRes->json('data.id');

        // 9. Descargar Receta Oficial en PDF
        $pdfRes = $this->get("/medical-records/{$medicalRecordId}/pdf");
        $pdfRes->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfRes->headers->get('content-type'));
    }
}

