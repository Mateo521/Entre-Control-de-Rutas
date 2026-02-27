<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Toll;
use Illuminate\Http\Request;

class TollController extends Controller
{
    /**
     * Lista todos los peajes activos.
     */
    public function index()
    {
        // En lugar de Toll::all() o Toll::get(), usamos paginate
        $tolls = Toll::latest()->paginate(15);
        return response()->json($tolls);
    }

    /**
     * Crea un nuevo peaje y define su esquema de datos dinámico.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',

            'dynamic_schema' => 'nullable|array',


            'dynamic_schema.inventory_fields.*.name' => 'required_with:dynamic_schema.inventory_fields|string',
            'dynamic_schema.inventory_fields.*.type' => 'required_with:dynamic_schema.inventory_fields|string',
        ]);

        $toll = Toll::create($validatedData);

        return response()->json([
            'message' => 'Peaje creado exitosamente',
            'data' => $toll
        ], 201);
    }

    /**
     * Muestra la información de un peaje específico.
     */
    public function show($id)
    {
        $toll = Toll::find($id);

        if (!$toll) {
            return response()->json(['message' => 'Peaje no encontrado'], 404);
        }

        return response()->json($toll, 200);
    }

    /**
     * Actualiza la información o el esquema dinámico de un peaje.
     */
    public function update(Request $request, $id)
    {
        $toll = Toll::find($id);

        if (!$toll) {
            return response()->json(['message' => 'Peaje no encontrado'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'dynamic_schema' => 'nullable|array',
        ]);

        $toll->update($validatedData);

        return response()->json([
            'message' => 'Peaje actualizado exitosamente',
            'data' => $toll
        ], 200);
    }

    /**
     * Archiva un peaje (Soft Delete). No lo elimina de la base de datos.
     */
    public function destroy($id)
    {
        $toll = Toll::find($id);

        if (!$toll) {
            return response()->json(['message' => 'Peaje no encontrado'], 404);
        }

        $toll->delete();

        return response()->json(['message' => 'Peaje archivado exitosamente'], 200);
    }
}