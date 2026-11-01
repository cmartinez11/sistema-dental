<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\InstallmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PaymentInstallment;
use App\Models\PaymentPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DentalModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_installment_scope_detects_overdue_cuotas(): void
    {
        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'dni' => '12345678',
        ]);

        $dentistUser = User::create([
            'name' => 'Dr. Ortiz',
            'email' => 'ortiz@dental.test',
            'password' => bcrypt('secret123'),
            'role' => UserRole::Dentista,
        ]);

        $dentist = Dentist::create([
            'user_id' => $dentistUser->id,
            'cmp_number' => 'COP-55443',
            'specialty' => 'Ortodoncia',
            'commission_percentage' => 15.00,
        ]);

        $plan = PaymentPlan::create([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'total_amount' => 3000.00,
            'discount' => 0.00,
            'final_amount' => 3000.00,
            'status' => 'in_progress',
        ]);

        // 1. Cuota vencida por fecha (status pending pero due_date en el pasado)
        $pastDue = PaymentInstallment::create([
            'payment_plan_id' => $plan->id,
            'installment_number' => 1,
            'amount' => 500.00,
            'due_date' => Carbon::now()->subDays(10)->toDateString(),
            'status' => InstallmentStatus::Pending,
        ]);

        // 2. Cuota vencida explícitamente con status overdue
        $explicitOverdue = PaymentInstallment::create([
            'payment_plan_id' => $plan->id,
            'installment_number' => 2,
            'amount' => 500.00,
            'due_date' => Carbon::now()->subDays(2)->toDateString(),
            'status' => InstallmentStatus::Overdue,
        ]);

        // 3. Cuota futura pendiente (no vencida)
        $futurePending = PaymentInstallment::create([
            'payment_plan_id' => $plan->id,
            'installment_number' => 3,
            'amount' => 500.00,
            'due_date' => Carbon::now()->addDays(20)->toDateString(),
            'status' => InstallmentStatus::Pending,
        ]);

        // 4. Cuota pagada
        $paid = PaymentInstallment::create([
            'payment_plan_id' => $plan->id,
            'installment_number' => 4,
            'amount' => 500.00,
            'due_date' => Carbon::now()->subDays(30)->toDateString(),
            'status' => InstallmentStatus::Paid,
            'paid_at' => Carbon::now()->subDays(30),
        ]);

        $overdueInstallments = PaymentInstallment::overdue()->pluck('id')->toArray();

        $this->assertContains($pastDue->id, $overdueInstallments);
        $this->assertContains($explicitOverdue->id, $overdueInstallments);
        $this->assertNotContains($futurePending->id, $overdueInstallments);
        $this->assertNotContains($paid->id, $overdueInstallments);

        $this->assertTrue($pastDue->isOverdue());
        $this->assertTrue($explicitOverdue->isOverdue());
        $this->assertFalse($futurePending->isOverdue());
        $this->assertFalse($paid->isOverdue());
        $this->assertEquals(10, $pastDue->daysOverdue());
    }

    public function test_appointment_conflict_scopes_detect_overlap(): void
    {
        $patient1 = Patient::create(['first_name' => 'Carlos', 'last_name' => 'Silva', 'dni' => '87654321']);
        $patient2 = Patient::create(['first_name' => 'Maria', 'last_name' => 'Gomez', 'dni' => '11223344']);

        $dentistUser = User::create([
            'name' => 'Dra. Flores',
            'email' => 'flores@dental.test',
            'password' => bcrypt('password'),
            'role' => UserRole::Dentista,
        ]);

        $dentist = Dentist::create([
            'user_id' => $dentistUser->id,
            'cmp_number' => 'COP-99887',
            'specialty' => 'Rehabilitación Oral',
        ]);

        $tomorrow = Carbon::tomorrow()->setHour(10)->setMinute(0)->setSecond(0);

        // Cita existente: 10:00 a 11:00 en el sillón 1
        $existing = Appointment::create([
            'patient_id' => $patient1->id,
            'dentist_id' => $dentist->id,
            'dental_chair' => 1,
            'start_time' => $tomorrow->copy(),
            'end_time' => $tomorrow->copy()->addHour(),
            'status' => AppointmentStatus::Scheduled,
        ]);

        // Intento 1: Misma hora (10:15 a 10:45) con el mismo dentista -> DEBE detectar conflicto
        $hasDentistConflict = Appointment::forDentistConflict(
            dentistId: $dentist->id,
            startTime: $tomorrow->copy()->addMinutes(15),
            endTime: $tomorrow->copy()->addMinutes(45)
        )->exists();
        $this->assertTrue($hasDentistConflict);

        // Intento 2: Misma hora (10:30 a 11:30) en el sillón 1 con otro paciente -> DEBE detectar conflicto de sillón
        $hasChairConflict = Appointment::forChairConflict(
            dentalChair: 1,
            startTime: $tomorrow->copy()->addMinutes(30),
            endTime: $tomorrow->copy()->addMinutes(90)
        )->exists();
        $this->assertTrue($hasChairConflict);

        // Intento 3: Horario inmediatamente posterior (11:00 a 12:00) -> NO DEBE detectar conflicto
        $noDentistConflict = Appointment::forDentistConflict(
            dentistId: $dentist->id,
            startTime: $tomorrow->copy()->addHour(),
            endTime: $tomorrow->copy()->addHours(2)
        )->exists();
        $this->assertFalse($noDentistConflict);

        $noChairConflict = Appointment::forChairConflict(
            dentalChair: 1,
            startTime: $tomorrow->copy()->addHour(),
            endTime: $tomorrow->copy()->addHours(2)
        )->exists();
        $this->assertFalse($noChairConflict);
    }
}

