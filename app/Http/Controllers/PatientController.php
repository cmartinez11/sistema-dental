<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    /**
     * Mostrar la vista principal del módulo de pacientes.
     */
    public function indexView(): View
    {
        return view('patients.index');
    }

    /**
     * Obtener listado o buscar pacientes (para la API en vivo o selectores).
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');

        $query = Patient::query()
            ->when($search, function ($q, $search) {
                return $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%");
            });

        // Retornar lista completa para selectores o paginado para tablas
        if ($request->boolean('all')) {
            $patients = $query->orderBy('first_name', 'asc')->get([
                'id',
                'first_name',
                'last_name',
                'dni',
                'phone',
                'allergies',
            ]);

            return response()->json([
                'count' => $patients->count(),
                'data' => $patients,
            ]);
        }

        $patients = $query->latest('id')->paginate(15);

        return response()->json($patients);
    }

    /**
     * Guardar un nuevo paciente.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'dni' => 'required|string|max:15|unique:patients,dni',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:500',
            'allergies' => 'nullable|string|max:1000',
        ]);

        $patient = Patient::create($validated);

        return response()->json([
            'message' => '¡Paciente registrado exitosamente!',
            'patient' => $patient,
        ], 201);
    }
}
