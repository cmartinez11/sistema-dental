<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Maneja la verificación de Control de Acceso Basado en Roles (RBAC).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles Roles autorizados (ej: 'admin', 'receptionist', 'dentist')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // 1. Si no hay sesión activa, redirigir al login
        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }
            return redirect()->route('login');
        }

        // 2. El Administrador general tiene acceso total y sin restricciones a cualquier módulo
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // 3. Verificar si el usuario posee alguno de los roles requeridos
        if ($user->hasRole(...$roles)) {
            return $next($request);
        }

        // 4. Acceso Denegado: Manejo limpio de respuesta según el tipo de petición
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Acceso denegado: su rol no tiene permisos para acceder a este recurso clínico.',
            ], 403);
        }

        // Redirección amigable a la pantalla operativa del rol con mensaje de advertencia
        $targetRoute = match (true) {
            $user->hasRole('dentist') => route('medical-records.index'),
            $user->hasRole('receptionist') => route('appointments.index'),
            default => route('login'),
        };

        // Prevención de bucles infinitos de redirección
        if ($request->url() === $targetRoute) {
            abort(403, 'Acceso denegado: no cuenta con permisos suficientes para este módulo.');
        }

        return redirect($targetRoute)->with('error', 'Acceso no autorizado: su perfil no tiene permisos para el módulo solicitado.');
    }
}

