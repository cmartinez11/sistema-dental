<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_access_patients_appointments_and_cash(): void
    {
        $receptionist = User::create([
            'name' => 'Ana Recepcionista',
            'email' => 'ana@sjl.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Recepcionista,
        ]);

        $this->actingAs($receptionist)->get('/dashboard/patients')->assertStatus(200);
        $this->actingAs($receptionist)->get('/dashboard/appointments')->assertStatus(200);
        $this->actingAs($receptionist)->get('/dashboard/cash')->assertStatus(200);
    }

    public function test_receptionist_cannot_access_medical_records(): void
    {
        $receptionist = User::create([
            'name' => 'Ana Recepcionista',
            'email' => 'ana@sjl.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Recepcionista,
        ]);

        $response = $this->actingAs($receptionist)->get('/dashboard/medical-records');
        $response->assertRedirect('/dashboard/appointments');
        $response->assertSessionHas('error');
    }

    public function test_dentist_can_access_patients_appointments_and_medical_records(): void
    {
        $dentist = User::create([
            'name' => 'Dr. Fernando',
            'email' => 'fernando@sjl.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Dentista,
        ]);

        $this->actingAs($dentist)->get('/dashboard/patients')->assertStatus(200);
        $this->actingAs($dentist)->get('/dashboard/appointments')->assertStatus(200);
        $this->actingAs($dentist)->get('/dashboard/medical-records')->assertStatus(200);
    }

    public function test_dentist_cannot_access_cash_register(): void
    {
        $dentist = User::create([
            'name' => 'Dr. Fernando',
            'email' => 'fernando@sjl.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Dentista,
        ]);

        $response = $this->actingAs($dentist)->get('/dashboard/cash');
        $response->assertRedirect('/dashboard/medical-records');
        $response->assertSessionHas('error');
    }

    public function test_admin_has_full_access_to_all_modules(): void
    {
        $admin = User::create([
            'name' => 'Admin General',
            'email' => 'admin@sjl.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Admin,
        ]);

        $this->actingAs($admin)->get('/dashboard')->assertStatus(200);
        $this->actingAs($admin)->get('/dashboard/patients')->assertStatus(200);
        $this->actingAs($admin)->get('/dashboard/appointments')->assertStatus(200);
        $this->actingAs($admin)->get('/dashboard/cash')->assertStatus(200);
        $this->actingAs($admin)->get('/dashboard/medical-records')->assertStatus(200);
    }
}

