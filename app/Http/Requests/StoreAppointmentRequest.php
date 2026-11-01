<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado tiene permisos para agendar citas.
     * Solo Recepcionistas y Administradores pueden gestionar la agenda de citas clínicas.
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
     * Limpia y normaliza los datos de entrada de la solicitud.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->notes !== null ? trim((string) $this->notes) : null,
            'dental_chair' => $this->dental_chair !== null ? (int) $this->dental_chair : 1,
        ]);
    }

    /**
     * Reglas estrictas de validación para la creación de una cita odontológica.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'patient_id' => [
                'required',
                'integer',
                'exists:patients,id',
            ],
            'dentist_id' => [
                'required',
                'integer',
                'exists:dentists,id',
            ],
            'treatment_id' => [
                'nullable',
                'integer',
                'exists:treatments,id',
            ],
            'dental_chair' => [
                'required',
                'integer',
                'min:1',
                'max:10', // Clínica con sillones 1 al 10
            ],
            'start_time' => [
                'required',
                'date',
                'after_or_equal:now',
            ],
            'end_time' => [
                'required',
                'date',
                'after:start_time',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Validaciones complementarias de lógica de negocio para tiempos clínicos.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->start_time === null || $this->end_time === null) {
                    return;
                }

                $start = Carbon::parse($this->start_time);
                $end = Carbon::parse($this->end_time);

                // 1. La cita debe iniciar y terminar el mismo día
                if (! $start->isSameDay($end)) {
                    $validator->errors()->add(
                        'end_time',
                        'La cita clínica debe iniciar y culminar en el mismo día laboral.'
                    );
                }

                // 2. Duración mínima y máxima de consulta (mínimo 15 minutos, máximo 4 horas)
                $durationInMinutes = $start->diffInMinutes($end);

                if ($durationInMinutes < 15) {
                    $validator->errors()->add(
                        'end_time',
                        'La duración mínima de una consulta odontológica debe ser de al menos 15 minutos.'
                    );
                }

                if ($durationInMinutes > 240) {
                    $validator->errors()->add(
                        'end_time',
                        'La duración de una cita individual no puede exceder las 4 horas continuas.'
                    );
                }

                // 3. Validación de horario de atención clínica (07:00 a 21:00)
                $startHour = (int) $start->format('H');
                $endHour = (int) $end->format('H');
                $endMinute = (int) $end->format('i');

                if ($startHour < 7 || $endHour > 21 || ($endHour === 21 && $endMinute > 0)) {
                    $validator->errors()->add(
                        'start_time',
                        'El horario seleccionado está fuera del horario de atención clínica (07:00 - 21:00).'
                    );
                }
            },
        ];
    }

    /**
     * Mensajes descriptivos en caso de fallo en validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'Debe seleccionar un paciente válido.',
            'patient_id.exists' => 'El paciente seleccionado no existe en los registros.',
            'dentist_id.required' => 'Debe asignar un odontólogo para la atención.',
            'dentist_id.exists' => 'El odontólogo seleccionado no existe en el sistema.',
            'treatment_id.exists' => 'El tratamiento seleccionado no es válido.',
            'dental_chair.required' => 'Debe indicar el número de sillón dental a ocupar.',
            'dental_chair.between' => 'El sillón dental seleccionado no es válido.',
            'start_time.required' => 'La fecha y hora de inicio de la cita es obligatoria.',
            'start_time.after_or_equal' => 'No es posible agendar citas en fechas u horas pasadas.',
            'end_time.required' => 'La fecha y hora de finalización es obligatoria.',
            'end_time.after' => 'La hora de finalización debe ser posterior a la hora de inicio.',
        ];
    }
}

