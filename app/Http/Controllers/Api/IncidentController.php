<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentController extends Controller
{


    public function index(Request $request)
    {
        // 1. Iniciamos la consulta base incluyendo la relación con el peaje
        $query = Incident::with('toll')->latest();

        // 2. Filtro Cruzado: Por ID de Peaje
        $query->when($request->filled('toll_id'), function ($q) use ($request) {
            return $q->where('toll_id', $request->toll_id);
        });

        // 3. Filtro Cruzado: Por Tipo de Suceso (Ej. 'pesaje_excedido')
        $query->when($request->filled('incident_type'), function ($q) use ($request) {
            return $q->where('incident_type', $request->incident_type);
        });

        // 4. Filtro Cruzado: Fecha Desde
        $query->when($request->filled('date_from'), function ($q) use ($request) {
            return $q->whereDate('created_at', '>=', $request->date_from);
        });

        // 5. Filtro Cruzado: Fecha Hasta
        $query->when($request->filled('date_to'), function ($q) use ($request) {
            return $q->whereDate('created_at', '<=', $request->date_to);
        });

        // 6. Ejecutamos la consulta filtrada y retornamos el JSON
        return response()->json($query->get());
    }
    public function store(Request $request)
    {
        // 1. Validamos los datos entrantes
        $validatedData = $request->validate([
            'toll_id' => 'required|exists:tolls,id',
            'incident_type' => 'required|string',
            'dynamic_data' => 'nullable|array'
        ]);

        // 2. Le inyectamos el ID del usuario que está logueado actualmente
        $validatedData['user_id'] = Auth::id();

        // 3. Guardamos en PostgreSQL
        $incident = Incident::create($validatedData);

        return response()->json([
            'message' => 'Suceso registrado exitosamente',
            'data' => $incident
        ], 201);
    }
}