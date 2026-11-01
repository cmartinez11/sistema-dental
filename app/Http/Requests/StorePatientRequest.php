<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado tiene permisos para registrar pacientes.
     * Solo Recepcionistas y Administradores tienen autorización para alta de pacientes en mostrador.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::Recepcionista);
    }

    /**
     * Limpia y normaliza los datos de entrada antes de aplicar las reglas de validación.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'dni' => $this->dni !== null ? strtoupper(trim((string) $this->dni)) : null,
            'first_name' => $this->first_name !== null ? trim((string) $this->first_name) : null,
            'last_name' => $this->last_name !== null ? trim((string) $this->last_name) : null,
            'phone' => $this->phone !== null ? trim((string) $this->phone) : null,
            'allergies' => $this->allergies !== null ? trim((string) $this->allergies) : null,
        ]);
    }

    /**
     * Reglas estrictas de validación para el registro de un nuevo paciente.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[\pL\s\-.]+$/u', // Solo letras, espacios, guiones y puntos
            ],
            'last_name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[\pL\s\-.]+$/u',
            ],
            'dni' => [
                'required',
                'string',
                'min:8',
                'max:15',
                'regex:/^[0-9]{8}$|^[A-Z0-9]{8,15}$/', // DNI estándar (8 dígitos) o CE/Pasaporte
                Rule::unique('patients', 'dni'),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\+?[0-9\s\-()]{7,20}$/',
            ],
            'address' => [
                'nullable',
                'string',
                'max:500',
            ],
            'birth_date' => [
                'nullable',
                'date',
                'before:today',
                'after:1900-01-01',
            ],
            'allergies' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Mensajes descriptivos en caso de incumplimiento de reglas.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre del paciente es obligatorio.',
            'first_name.regex' => 'El nombre del paciente solo puede contener letras y espacios.',
            'last_name.required' => 'Los apellidos del paciente son obligatorios.',
            'last_name.regex' => 'Los apellidos del paciente solo pueden contener letras y espacios.',
            'dni.required' => 'El número de DNI o documento de identidad es obligatorio.',
            'dni.regex' => 'El DNI debe tener 8 dígitos numéricos o formato de carné de extranjería válido.',
            'dni.unique' => 'Este número de documento ya se encuentra registrado en el sistema.',
            'phone.regex' => 'El formato del número de teléfono no es válido.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior al día de hoy.',
            'birth_date.after' => 'La fecha de nacimiento ingresada no es válida.',
        ];
    }
}

