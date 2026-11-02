<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\MedicalRecord;
use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Validar mediante la Policy que el usuario autenticado puede crear historias clínicas (solo dentistas/admin)
        return (bool) $this->user()?->can('create', MedicalRecord::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'dentist_id' => ['required', 'exists:dentists,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'diagnosis' => ['required', 'string', 'max:2000'],
            'evolution_notes' => ['required', 'string', 'max:3000'],
            'prescription' => ['nullable', 'string', 'max:1500'],
        ];
    }
}
