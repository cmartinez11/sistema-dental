<?php

namespace App\Http\Controllers;

use App\Models\Dentist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DentistController extends Controller
{
    /**
     * Retorna el catálogo de cirujanos dentistas y especialistas activos con su usuario asociado.
     */
    public function index(Request $request): JsonResponse
    {
        $dentists = Dentist::with('user:id,name,email')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'count' => $dentists->count(),
            'data' => $dentists,
        ]);
    }
}

