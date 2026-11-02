<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\DentistSchedule;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DentistScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $receptionist;
    private Dentist $dentist;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@sjl.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Admin,
        ]);

        $this->receptionist = User::create([
            'name' => 'Recepcionista Test',
            'email' => 'recepcion@sjl.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Recepcionista,
        ]);

        $dentistUser = User::create([
            'name' => 'Dr. Carlos Ruiz',
            'email' => 'carlos.ruiz@sjl.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Dentista,
        ]);

        $this->dentist = Dentist::create([
            'user_id' => $dentistUser->id,
            'cmp_number' => 'COP-55443',
            'specialty' => 'Ortodoncia',
        ]);

        $this->patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'dni' => '12345678',
        ]);
    }

    public function test_admin_can_view_and_update_dentist_schedules(): void
    {
        // 1. Cargar turnos iniciales
        $response = $this->actingAs($this->admin)->getJson("/dashboard/dentist-schedules/{$this->dentist->id}");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'dentist' => ['id', 'name', 'cmp_number', 'specialty'],
            'schedules' => [
                '*' => ['day_of_week', 'day_name', 'start_time', 'end_time', 'is_active'],
            ],
        ]);

        // 2. Actualizar horario: Lunes a Viernes de 09:00 a 17:00, Sábado no labora
        $updateData = [
            'schedules' => [
                ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true],
                ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true],
                ['day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true],
                ['day_of_week' => 4, 'start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true],
                ['day_of_week' => 5, 'start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true],
                ['day_of_week' => 6, 'start_time' => '09:00', 'end_time' => '13:00', 'is_active' => false], // Sábado inactivo
                ['day_of_week' => 7, 'start_time' => '09:00', 'end_time' => '13:00', 'is_active' => false], // Domingo inactivo
            ],
        ];

        $saveResponse = $this->actingAs($this->admin)->postJson("/dashboard/dentist-schedules/{$this->dentist->id}", $updateData);
        $saveResponse->assertStatus(200);

        $this->assertDatabaseHas('dentist_schedules', [
            'dentist_id' => $this->dentist->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('dentist_schedules', [
            'dentist_id' => $this->dentist->id,
            'day_of_week' => 6,
            'is_active' => 0,
        ]);
    }

    public function test_scheduling_fails_when_appointment_is_outside_dentist_shift(): void
    {
        // Configurar turno de Lunes: 09:00 a 17:00
        DentistSchedule::updateOrCreate(
            ['dentist_id' => $this->dentist->id, 'day_of_week' => 1],
            ['start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => true]
        );

        // Próximo Lunes
        $nextMonday = Carbon::now()->next(Carbon::MONDAY);
        
        // Intento de cita a las 07:30 AM (fuera del turno de 09:00 a 17:00)
        $earlyStart = $nextMonday->copy()->setHour(7)->setMinute(30)->setSecond(0);
        $earlyEnd = $earlyStart->copy()->addMinutes(45);

        $response = $this->actingAs($this->receptionist)->postJson('/api/appointments', [
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'dental_chair' => 1,
            'start_time' => $earlyStart->toDateTimeString(),
            'end_time' => $earlyEnd->toDateTimeString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_time']);
        $this->assertStringContainsString('fuera del turno laboral', $response->json('errors.start_time.0'));
    }

    public function test_scheduling_fails_when_dentist_does_not_work_on_that_day(): void
    {
        // Configurar Domingo como inactivo
        DentistSchedule::updateOrCreate(
            ['dentist_id' => $this->dentist->id, 'day_of_week' => 7],
            ['start_time' => '09:00:00', 'end_time' => '14:00:00', 'is_active' => false]
        );

        // Próximo Domingo
        $nextSunday = Carbon::now()->next(Carbon::SUNDAY);
        $startTime = $nextSunday->copy()->setHour(10)->setMinute(0);
        $endTime = $startTime->copy()->addMinutes(45);

        $response = $this->actingAs($this->receptionist)->postJson('/api/appointments', [
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'dental_chair' => 1,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_time']);
        $this->assertStringContainsString('no atiende citas los días Domingo', $response->json('errors.start_time.0'));
    }

    public function test_scheduling_succeeds_when_within_active_shift(): void
    {
        // Configurar Martes: 09:00 a 18:00
        DentistSchedule::updateOrCreate(
            ['dentist_id' => $this->dentist->id, 'day_of_week' => 2],
            ['start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_active' => true]
        );

        // Próximo Martes a las 11:00 AM (dentro del turno)
        $nextTuesday = Carbon::now()->next(Carbon::TUESDAY);
        $startTime = $nextTuesday->copy()->setHour(11)->setMinute(0)->setSecond(0);
        $endTime = $startTime->copy()->addMinutes(45);

        $response = $this->actingAs($this->receptionist)->postJson('/api/appointments', [
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'dental_chair' => 1,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('appointments', [
            'dentist_id' => $this->dentist->id,
            'patient_id' => $this->patient->id,
            'dental_chair' => 1,
        ]);
    }
}

