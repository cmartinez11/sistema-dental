<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_recepcionista_can_schedule_appointment_without_conflict(): void
    {
        $recepcionista = User::create([
            'name' => 'Lucia Recepcionista',
            'email' => 'lucia@dental.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Recepcionista,
        ]);

        $patient = Patient::create([
            'first_name' => 'Roberto',
            'last_name' => 'Paredes',
            'dni' => '44556677',
        ]);

        $dentistUser = User::create([
            'name' => 'Dr. Suarez',
            'email' => 'suarez@dental.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Dentista,
        ]);

        $dentist = Dentist::create([
            'user_id' => $dentistUser->id,
            'cmp_number' => 'COP-77665',
            'specialty' => 'Ortodoncia',
        ]);

        $startTime = Carbon::tomorrow()->setHour(9)->setMinute(0)->setSecond(0);
        $endTime = $startTime->copy()->addMinutes(45);

        $response = $this->actingAs($recepcionista)->postJson(route('appointments.store'), [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'dental_chair' => 1,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
            'notes' => 'Primera consulta ortodoncia',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'dental_chair' => 1,
            'status' => AppointmentStatus::Scheduled->value,
        ]);
    }

    public function test_scheduling_fails_when_dentist_or_chair_has_conflict(): void
    {
        $recepcionista = User::create([
            'name' => 'Lucia Recepcionista',
            'email' => 'lucia2@dental.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Recepcionista,
        ]);

        $patient1 = Patient::create(['first_name' => 'Ana', 'last_name' => 'Vega', 'dni' => '12121212']);
        $patient2 = Patient::create(['first_name' => 'Luis', 'last_name' => 'Ramos', 'dni' => '34343434']);

        $dentistUser = User::create([
            'name' => 'Dr. Suarez',
            'email' => 'suarez2@dental.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Dentista,
        ]);

        $dentist = Dentist::create([
            'user_id' => $dentistUser->id,
            'cmp_number' => 'COP-11223',
            'specialty' => 'Ortodoncia',
        ]);

        $startTime = Carbon::tomorrow()->setHour(14)->setMinute(0)->setSecond(0);
        $endTime = $startTime->copy()->addHour();

        // Primera cita
        Appointment::create([
            'patient_id' => $patient1->id,
            'dentist_id' => $dentist->id,
            'dental_chair' => 2,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => AppointmentStatus::Scheduled,
        ]);

        // Intento de cruce con el mismo odontólogo (aunque sea en otro sillón)
        $conflictDentist = $this->actingAs($recepcionista)->postJson(route('appointments.store'), [
            'patient_id' => $patient2->id,
            'dentist_id' => $dentist->id,
            'dental_chair' => 3,
            'start_time' => $startTime->copy()->addMinutes(15)->toDateTimeString(),
            'end_time' => $startTime->copy()->addMinutes(45)->toDateTimeString(),
        ]);

        $conflictDentist->assertStatus(422);
        $conflictDentist->assertJsonValidationErrors(['dentist_id']);

        // Crear otro dentista
        $otherDentistUser = User::create([
            'name' => 'Dra. Rios',
            'email' => 'rios@dental.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Dentista,
        ]);
        $otherDentist = Dentist::create([
            'user_id' => $otherDentistUser->id,
            'cmp_number' => 'COP-44332',
            'specialty' => 'Endodoncia',
        ]);

        // Intento de cruce en el mismo sillón (#2) con otro odontólogo
        $conflictChair = $this->actingAs($recepcionista)->postJson(route('appointments.store'), [
            'patient_id' => $patient2->id,
            'dentist_id' => $otherDentist->id,
            'dental_chair' => 2,
            'start_time' => $startTime->copy()->addMinutes(10)->toDateTimeString(),
            'end_time' => $startTime->copy()->addMinutes(50)->toDateTimeString(),
        ]);

        $conflictChair->assertStatus(422);
        $conflictChair->assertJsonValidationErrors(['dental_chair']);
    }

    public function test_dentist_cannot_schedule_appointments(): void
    {
        $dentistUser = User::create([
            'name' => 'Dr. Suarez',
            'email' => 'dentist@dental.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Dentista,
        ]);

        $patient = Patient::create(['first_name' => 'Pedro', 'last_name' => 'Alva', 'dni' => '55667788']);

        $startTime = Carbon::tomorrow()->setHour(10)->setMinute(0);

        // Dentista intenta agendar cita en mostrador -> 403 Forbidden por Policy
        $response = $this->actingAs($dentistUser)->postJson(route('appointments.store'), [
            'patient_id' => $patient->id,
            'dentist_id' => 1,
            'dental_chair' => 1,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $startTime->copy()->addMinutes(30)->toDateTimeString(),
        ]);

        $response->assertStatus(403);
    }
}

