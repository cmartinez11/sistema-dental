<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAppointmentRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado tiene permisos para actualizar o reprogramar citas.
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
     * Normaliza los datos antes de la validación.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('notes')) {
            $this->merge([
                'notes' => $this->notes !== null ? trim((string) $this->notes) : null,
            ]);
        }
    }

    /**
     * Reglas de validación para reprogramar o modificar una cita odontológica.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'dentist_id' => [
                'sometimes',
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
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:10',
            ],
            'start_time' => [
                'sometimes',
                'required',
                'date',
            ],
            'end_time' => [
                'sometimes',
                'required',
                'date',
                'after:start_time',
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::enum(AppointmentStatus::class),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Validaciones adicionales de tiempos clínicos al reprogramar.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->has('start_time') || ! $this->has('end_time')) {
                    return;
                }

                $start = Carbon::parse($this->start_time);
                $end = Carbon::parse($this->end_time);

                if (! $start->isSameDay($end)) {
                    $validator->errors()->add(
                        'end_time',
                        'La cita clínica reprogramada debe iniciar y finalizar en el mismo día.'
                    );
                }

                $durationInMinutes = $start->diffInMinutes($end);

                if ($durationInMinutes < 15) {
                    $validator->errors()->add(
                        'end_time',
                        'La duración mínima de la cita debe ser de 15 minutos.'
                    );
                }

                if ($durationInMinutes > 240) {
                    $validator->errors()->add(
                        'end_time',
                        'La duración de una cita no puede exceder las 4 horas continuas.'
                    );
                }
            },
        ];
    }
}

