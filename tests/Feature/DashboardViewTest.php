<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_login_via_web_and_access_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Dr. Cristhian Admin',
            'email' => 'admin@dental.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Admin,
        ]);

        $loginResponse = $this->post('/login', [
            'email' => 'admin@dental.test',
            'password' => 'password123',
        ]);
        $loginResponse->assertRedirect('/dashboard');

        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('¡Bienvenido(a), Dr. Cristhian Admin!');
        $dashboardResponse->assertSee('Total Pacientes');
        $dashboardResponse->assertSee('Citas para Hoy');
        $dashboardResponse->assertSee('Dentistas Activos');
        $dashboardResponse->assertSee('Ingresos del Día');
        $dashboardResponse->assertSee('/dashboard/patients');
        $dashboardResponse->assertSee('/dashboard/appointments');
        $dashboardResponse->assertSee('/dashboard/cash');
    }

    public function test_receptionist_accessing_dashboard_is_redirected_to_appointments(): void
    {
        $receptionist = User::create([
            'name' => 'Lucía Recepción',
            'email' => 'lucia@dental.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Recepcionista,
        ]);

        $response = $this->actingAs($receptionist)->get('/dashboard');
        $response->assertRedirect('/dashboard/appointments');
        $response->assertSessionHas('error');
    }

    public function test_dentist_accessing_dashboard_is_redirected_to_medical_records(): void
    {
        $dentist = User::create([
            'name' => 'Dr. Carlos Dentista',
            'email' => 'carlos@dental.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Dentista,
        ]);

        $response = $this->actingAs($dentist)->get('/dashboard');
        $response->assertRedirect('/dashboard/medical-records');
        $response->assertSessionHas('error');
    }
}
