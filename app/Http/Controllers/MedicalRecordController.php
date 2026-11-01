<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreMedicalRecordRequest;
use App\Models\MedicalRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;


class MedicalRecordController extends Controller
{

    public function downloadPdf($id): Response
    {
        $medicalRecord = MedicalRecord::with(['patient', 'dentist.user'])->findOrFail($id);

        // Cargar la vista Blade pasándole los datos
        $pdf = Pdf::loadView('pdf.prescription', compact('medicalRecord'));

        // Retornar el PDF para descarga directa o visualización en navegador
        return $pdf->stream('receta-medica-' . $medicalRecord->id . '.pdf');
    }

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
                'data' => $medicalRecord->load(['patient', 'dentist', 'appointment'])
            ], 201);

        } catch (Exception $e) {
            report($e);

            return response()->json([
                'message' => 'Ocurrió un error interno al registrar la historia clínica.'
            ], 500);
        }
    }
}
