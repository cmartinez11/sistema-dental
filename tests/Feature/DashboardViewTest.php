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

    public function test_user_can_login_via_web_and_access_dashboard_without_redirection(): void
    {
        $user = User::create([
            'name' => 'Lucía Recepción',
            'email' => 'lucia@dental.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Recepcionista,
        ]);

        // Login vía web POST
        $loginResponse = $this->post('/login', [
            'email' => 'lucia@dental.test',
            'password' => 'password123',
        ]);
        $loginResponse->assertRedirect('/dashboard');

        // Acceso al Dashboard manteniendo la sesión web
        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('¡Bienvenido(a), Lucía Recepción!');
    }

    public function test_dashboard_renders_dental_kpi_and_quick_access_view(): void
    {
        $user = User::create([
            'name' => 'Dr. Cristhian',
            'email' => 'dr.cristhian@dental.test',
            'password' => bcrypt('password123'),
            'role' => UserRole::Dentista,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('¡Bienvenido(a), Dr. Cristhian!');
        $response->assertSee('Total Pacientes');
        $response->assertSee('Citas para Hoy');
        $response->assertSee('Dentistas Activos');
        $response->assertSee('Ingresos del Día');
        $response->assertSee('/dashboard/patients');
        $response->assertSee('/dashboard/appointments');
        $response->assertSee('/dashboard/cash');
    }
}
