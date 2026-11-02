<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Iniciar sesión, generar token de Sanctum y sincronizar la sesión web.
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Eliminar tokens anteriores para mantener una sola sesión activa
        $user->tokens()->delete();

        // Crear nuevo token Sanctum para clientes API
        $token = $user->createToken('dental-token')->plainTextToken;

        // Sincronizar sesión web para vistas Blade si la petición maneja sesión
        if ($request->hasSession()) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();
        }

        if (! $request->wantsJson()) {
            return redirect()->intended(route('dashboard'));
        }

        return response()->json([
            'message' => '¡Bienvenido al sistema, ' . $user->name . '!',
            'token' => $token,
            'role' => $user->role,
            'user' => $user,
        ]);
    }

    /**
     * Cerrar sesión (revocar token Sanctum y cerrar sesión web).
     */
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Sesión cerrada exitosamente.',
            ]);
        }

        return redirect()->route('login');
    }
}
