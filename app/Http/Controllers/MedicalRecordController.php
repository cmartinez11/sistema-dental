<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicalRecordRequest;
use App\Models\MedicalRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MedicalRecordController extends Controller
{
    /**
     * Lista notas de evolución e historias clínicas con relaciones cargadas para evitar N+1.
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 20);

        $query = MedicalRecord::with([
            'patient:id,first_name,last_name,dni,allergies',
            'dentist.user:id,name',
            'appointment:id,start_time,dental_chair',
        ]);

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->integer('patient_id'));
        }

        if ($request->filled('dentist_id')) {
            $query->where('dentist_id', $request->integer('dentist_id'));
        }

        $records = $query->latest('id')->take($limit)->get();

        return response()->json([
            'count' => $records->count(),
            'data' => $records,
        ]);
    }

    /**
     * Descarga o visualiza la receta médica oficial en formato PDF.
     */
    public function downloadPdf(int $id): Response
    {
        $medicalRecord = MedicalRecord::with(['patient', 'dentist.user'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.prescription', compact('medicalRecord'));

        return $pdf->stream('receta-medica-' . $medicalRecord->id . '.pdf');
    }

    /**
     * Guarda una nueva ficha de evolución médica.
     */
    public function store(StoreMedicalRecordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $medicalRecord = DB::transaction(function () use ($validated) {
                return MedicalRecord::create([
                    'patient_id' => $validated['patient_id'],
                    'dentist_id' => $validated['dentist_id'],
                    'appointment_id' => $validated['appointment_id'] ?? null,
                    'diagnosis' => $validated['diagnosis'],
                    'evolution_notes' => $validated['evolution_notes'],
                    'prescription' => $validated['prescription'] ?? null,
                ]);
            });

            return response()->json([
                'message' => 'Historia clínica y notas de evolución guardadas exitosamente.',
                'data' => $medicalRecord->load(['patient', 'dentist.user', 'appointment']),
            ], 201);

        } catch (Exception $e) {
            report($e);

            return response()->json([
                'message' => 'Ocurrió un error interno al registrar la historia clínica.',
            ], 500);
        }
    }
}
