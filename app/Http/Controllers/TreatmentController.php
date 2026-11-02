<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TreatmentController extends Controller
{
    /**
     * Retorna el catálogo de tratamientos dentales y aranceles clínicos.
     */
    public function index(Request $request): JsonResponse
    {
        $treatments = Treatment::orderBy('name', 'asc')->get();

        return response()->json([
            'count' => $treatments->count(),
            'data' => $treatments,
        ]);
    }
}

