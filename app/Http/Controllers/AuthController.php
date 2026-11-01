<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Iniciar sesión y generar token de Sanctum.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Eliminar tokens anteriores (opcional, para mantener una sola sesión activa)
        $user->tokens()->delete();

        // Crear un nuevo token de acceso
        $token = $user->createToken('dental-token')->plainTextToken;

        return response()->json([
            'message' => '¡Bienvenido al sistema, ' . $user->name . '!',
            'token' => $token,
            'role' => $user->role,
            'user' => $user
        ]);
    }

    /**
     * Cerrar sesión (revocar token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.'
        ]);
    }
}
