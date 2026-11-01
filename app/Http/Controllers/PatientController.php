<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Mostrar la vista principal del módulo de pacientes.
     */
    public function indexView()
    {
        return view('patients.index');
    }

    /**
     * Obtener listado o buscar pacientes (para la API en vivo).
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $patients = Patient::when($search, function ($query, $search) {
            return $query->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('dni', 'like', "%{$search}%");
        })
            ->latest()
            ->paginate(10);

        return response()->json($patients);
    }

    /**
     * Guardar un nuevo paciente.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'dni' => 'required|string|max:15|unique:patients,dni',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        $patient = Patient::create($validated);

        return response()->json([
            'message' => '¡Paciente registrado exitosamente!',
            'patient' => $patient
        ], 201);
    }
}