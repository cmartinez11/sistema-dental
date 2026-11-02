<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\InstallmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\CashRegister;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\PaymentInstallment;
use App\Models\PaymentPlan;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Población de datos base para la Clínica Dental en San Juan de Lurigancho.
     */
    public function run(): void
    {
        // 1. Usuarios del Sistema
        $admin = User::firstOrCreate(
            ['email' => 'admin@dental.pe'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('admin123'),
                'role' => UserRole::Admin,
            ]
        );

        $receptionist = User::firstOrCreate(
            ['email' => 'recepcion@dental.pe'],
            [
                'name' => 'Lucía Mendoza (Recepción)',
                'password' => Hash::make('recepcion123'),
                'role' => UserRole::Recepcionista,
            ]
        );

        $dentistUser = User::firstOrCreate(
            ['email' => 'dentista@dental.pe'],
            [
                'name' => 'Dr. Carlos Ruiz Ortiz',
                'password' => Hash::make('dentista123'),
                'role' => UserRole::Dentista,
            ]
        );

        // 2. Perfil de Especialista Odontológico
        $dentist = Dentist::firstOrCreate(
            ['user_id' => $dentistUser->id],
            [
                'cmp_number' => 'COP-55443',
                'specialty' => 'Ortodoncia y Ortopedia Maxilar',
                'commission_percentage' => 15.00,
            ]
        );

        // 3. Catálogo de Tratamientos Odontológicos en Soles (PEN)
        $treatmentList = [
            [
                'name' => 'Profilaxis y Destartraje con Ultrasonido',
                'description' => 'Eliminación completa de cálculo dental, pulido coronario y fluorización.',
                'base_price' => 80.00,
                'estimated_duration_minutes' => 30,
            ],
            [
                'name' => 'Curación con Resina Fotocurable Estética 3M',
                'description' => 'Restauración estética de pieza dental posterior o anterior.',
                'base_price' => 70.00,
                'estimated_duration_minutes' => 40,
            ],
            [
                'name' => 'Tratamiento de Ortodoncia con Brackets Metálicos',
                'description' => 'Instalación de aparatología fija completa superior e inferior.',
                'base_price' => 2400.00,
                'estimated_duration_minutes' => 60,
            ],
            [
                'name' => 'Endodoncia Mecanizada Multirradicular',
                'description' => 'Tratamiento de conducto con obturación termoplástica en molares.',
                'base_price' => 350.00,
                'estimated_duration_minutes' => 60,
            ],
            [
                'name' => 'Blanqueamiento Dental LED en Consultorio',
                'description' => 'Aclaramiento dental profesional con peróxido de hidrógeno al 35%.',
                'base_price' => 250.00,
                'estimated_duration_minutes' => 45,
            ],
        ];

        foreach ($treatmentList as $tData) {
            Treatment::firstOrCreate(['name' => $tData['name']], $tData);
        }

        // 4. Pacientes de San Juan de Lurigancho
        $patient1 = Patient::firstOrCreate(
            ['dni' => '72849102'],
            [
                'first_name' => 'Roberto Carlos',
                'last_name' => 'Paredes Quispe',
                'phone' => '987654321',
                'address' => 'Av. Próceres de la Independencia 1420, SJL',
                'birth_date' => '1995-06-15',
                'allergies' => 'Alérgico a la Penicilina',
            ]
        );

        $patient2 = Patient::firstOrCreate(
            ['dni' => '44819230'],
            [
                'first_name' => 'María Elena',
                'last_name' => 'Vargas Huamán',
                'phone' => '912345678',
                'address' => 'Jr. Las Flores 340, San Juan de Lurigancho',
                'birth_date' => '1988-11-20',
                'allergies' => null,
            ]
        );

        // 5. Caja Diaria Abierta por Defecto para Recepción
        $cashRegister = CashRegister::firstOrCreate(
            [
                'user_id' => $receptionist->id,
                'status' => 'open',
            ],
            [
                'opening_amount' => 200.00,
                'opened_at' => Carbon::now()->startOfDay()->addHours(8),
            ]
        );

        // 6. Plan de Tratamiento Fraccionado (Ortodoncia)
        $plan = PaymentPlan::firstOrCreate(
            ['patient_id' => $patient1->id],
            [
                'dentist_id' => $dentist->id,
                'total_amount' => 2400.00,
                'discount' => 0.00,
                'final_amount' => 2400.00,
                'status' => 'in_progress',
            ]
        );

        // Cuotas de ortodoncia (1 vencida, 1 pendiente, cuotas mensuales)
        PaymentInstallment::firstOrCreate(
            ['payment_plan_id' => $plan->id, 'installment_number' => 1],
            [
                'amount' => 200.00,
                'due_date' => Carbon::now()->subDays(12)->toDateString(),
                'status' => InstallmentStatus::Overdue,
            ]
        );

        PaymentInstallment::firstOrCreate(
            ['payment_plan_id' => $plan->id, 'installment_number' => 2],
            [
                'amount' => 200.00,
                'due_date' => Carbon::now()->addDays(18)->toDateString(),
                'status' => InstallmentStatus::Pending,
            ]
        );

        // 7. Citas para Hoy
        $treatmentOrtodoncia = Treatment::where('name', 'like', '%Ortodoncia%')->first();

        Appointment::firstOrCreate(
            [
                'patient_id' => $patient1->id,
                'start_time' => Carbon::today()->setHour(10)->setMinute(0),
            ],
            [
                'dentist_id' => $dentist->id,
                'treatment_id' => $treatmentOrtodoncia?->id,
                'dental_chair' => 1,
                'end_time' => Carbon::today()->setHour(10)->setMinute(45),
                'status' => AppointmentStatus::Scheduled,
                'notes' => 'Ajuste mensual de arcos de ortodoncia',
            ]
        );
    }
}
