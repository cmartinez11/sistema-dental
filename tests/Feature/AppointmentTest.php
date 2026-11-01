<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Dentist;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Enums\UserRole;

class AppointmentTest extends TestCase
{
    use RefreshDatabase; // Limpia y migra la base de datos de pruebas en cada ejecución

    public function test_receptionist_can_create_appointment()
    {
        // 1. Crear un usuario recepcionista
        $user = User::create([
            'name' => 'Recepcionista Test',
            'email' => 'recep@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::Recepcionista
        ]);

        // 2. Crear un paciente de prueba
        $patient = Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Perez',
            'dni' => '12345678',
        ]);

        // 3. Crear un usuario dentista y su perfil asociado
        $userDentist = User::create([
            'name' => 'Dr. House',
            'email' => 'doctor@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::Dentista
        ]);

        $dentist = Dentist::create([
            'user_id' => $userDentist->id,
            'cmp_number' => 'CMP12345',
            'specialty' => 'Ortodoncia',
        ]);

        // 4. Simular la petición HTTP POST como el usuario autenticado
        $response = $this->actingAs($user)->postJson('/api/appointments', [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'start_time' => now()->addDay()->setHour(10)->toDateTimeString(),
            'end_time' => now()->addDay()->setHour(11)->toDateTimeString(),
        ]);

        // 5. Verificar que se creó correctamente (Código 201) y existe en base de datos
        $response->assertStatus(201);
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
        ]);
    }

    public function test_cannot_create_conflicting_appointment()
    {
        $user = User::create([
            'name' => 'Recepcionista Test',
            'email' => 'recep2@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::Recepcionista
        ]);

        $patient = Patient::create([
            'first_name' => 'Maria',
            'last_name' => 'Gomez',
            'dni' => '87654321',
        ]);

        $userDentist = User::create([
            'name' => 'Dr. Smith',
            'email' => 'doctor2@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::Dentista
        ]);

        $dentist = Dentist::create([
            'user_id' => $userDentist->id,
            'cmp_number' => 'CMP67890',
            'specialty' => 'General',
        ]);

        $startTime = now()->addDay()->setHour(10);
        $endTime = now()->addDay()->setHour(11);

        // Creamos una cita previa ocupando ese horario exacto
        Appointment::create([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'scheduled',
        ]);

        // Intentamos agendar otra cita en el mismo horario con el mismo dentista
        $response = $this->actingAs($user)->postJson('/api/appointments', [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
        ]);

        // El sistema debe rechazarlo estrictamente con código 422 (Unprocessable Entity)
        $response->assertStatus(422);
    }
}