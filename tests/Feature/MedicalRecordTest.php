<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Patient;
use App\Models\Dentist;
use App\Enums\UserRole;
use Tests\TestCase;

class MedicalRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_dentist_can_create_medical_record()
    {
        // 1. Crear usuario dentista
        $userDentist = User::create([
            'name' => 'Dr. House',
            'email' => 'house@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::Dentista
        ]);

        $dentist = Dentist::create([
            'user_id' => $userDentist->id,
            'cmp_number' => 'CMP55555',
            'specialty' => 'Rehabilitación Oral',
        ]);

        // 2. Crear un paciente
        $patient = Patient::create([
            'first_name' => 'Ana',
            'last_name' => 'Torres',
            'dni' => '98765432',
        ]);

        // 3. Simular la petición HTTP POST como el dentista autenticado
        $response = $this->actingAs($userDentist)->postJson('/api/medical-records', [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'diagnosis' => 'Caries profunda en pieza 36',
            'evolution_notes' => 'Se realizó exodoncia sin complicaciones.',
            'prescription' => 'Amoxicilina 500mg cada 8 horas por 5 días.',
        ]);

        // 4. Validar éxito (201) y registro en BD
        $response->assertStatus(201);
        $this->assertDatabaseHas('medical_records', [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'diagnosis' => 'Caries profunda en pieza 36',
        ]);
    }

    public function test_receptionist_cannot_create_medical_record()
    {
        // 1. Crear usuario recepcionista
        $userReceptionist = User::create([
            'name' => 'Recepcionista Carla',
            'email' => 'carla@test.com',
            'password' => bcrypt('password'),
            'role' => UserRole::Recepcionista
        ]);

        $patient = Patient::create([
            'first_name' => 'Pedro',
            'last_name' => 'Suarez',
            'dni' => '11223344',
        ]);

        // 2. Intentar crear historia clínica como recepcionista
        $response = $this->actingAs($userReceptionist)->postJson('/api/medical-records', [
            'patient_id' => $patient->id,
            'dentist_id' => 1,
            'diagnosis' => 'Intento de acceso no autorizado',
            'evolution_notes' => 'Esto debe ser bloqueado por la policy.',
        ]);

        // 3. El sistema debe denegar el acceso estrictamente con código 403 (Forbidden)
        $response->assertStatus(403);
    }
}
