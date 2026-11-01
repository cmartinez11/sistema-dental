<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Patient;
use App\Models\Dentist;
use App\Models\MedicalRecord;
use App\Enums\UserRole;

class MedicalRecordTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear usuario y perfil de dentista
        $user = User::firstOrCreate(
            ['email' => 'dr.test@clinica.pe'],
            [
                'name' => 'Dr. Carlos Ruiz',
                'password' => bcrypt('password'),
                'role' => UserRole::Dentista,
            ]
        );

        $dentist = Dentist::firstOrCreate(
            ['user_id' => $user->id],
            [
                'cmp_number' => 'CMP88888',
                'specialty' => 'Rehabilitación Oral',
            ]
        );

        // 2. Crear un paciente de prueba
        $patient = Patient::firstOrCreate(
            ['dni' => '45612378'],
            [
                'first_name' => 'Lucía',
                'last_name' => 'Fernández',
            ]
        );

        // 3. Crear la historia clínica (ID 1)
        MedicalRecord::firstOrCreate(
            ['id' => 1],
            [
                'patient_id' => $patient->id,
                'dentist_id' => $dentist->id,
                'diagnosis' => 'Pulpitis irreversible en pieza 46. Requiere tratamiento de conducto (endodoncia).',
                'evolution_notes' => 'Se realizó apertura y biopulpectomía de urgencia. Paciente sin dolor al finalizar la sesión.',
                'prescription' => 'Ibuprofeno 600mg: 1 cada 8 horas por 3 días en caso de dolor.\nAmoxicilina 875mg / Ácido Clavulánico 125mg: 1 cada 12 horas por 5 días.',
            ]
        );
    }
}