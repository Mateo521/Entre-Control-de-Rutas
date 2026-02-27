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
        // Traemos los sucesos. Si el frontend pide los archivados, usamos onlyTrashed()
        $query = Incident::with('toll')->latest();
        
        if ($request->boolean('archived')) {
            $query->onlyTrashed();
        }

        return response()->json($query->get());
    }

    // Nuevo método para obtener un solo registro (para la vista de Edición)
    public function show($id)
    {
        // withTrashed() permite ver el registro aunque esté archivado
        $incident = Incident::withTrashed()->with('toll')->findOrFail($id);
        return response()->json($incident);
    }

    public function store(Request $request)
    {
        $request->validate([
            'toll_id' => 'required|exists:tolls,id',
            'incident_type' => 'required|string',
            'dynamic_data' => 'nullable|string',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,mp4,pdf|max:20480',
        ]);

        $dynamicData = $request->filled('dynamic_data') ? json_decode($request->dynamic_data, true) : [];
        $mediaPaths = [];

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $campoNombre => $archivo) {
                $path = $archivo->store('sucesos', 'public');
                $mediaPaths[$campoNombre] = '/storage/' . $path;
            }
        }

        $incident = Incident::create([
            'toll_id' => $request->toll_id,
            'incident_type' => $request->incident_type,
            'user_id' => Auth::id(),
            'dynamic_data' => $dynamicData,
            'media_paths' => $mediaPaths
        ]);

        return response()->json(['message' => 'Suceso registrado exitosamente', 'data' => $incident], 201);
    }

    public function update(Request $request, $id)
    {
        $incident = Incident::findOrFail($id);

        $request->validate([
            'toll_id' => 'required|exists:tolls,id',
            'incident_type' => 'required|string',
            'dynamic_data' => 'nullable|string',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,mp4,pdf|max:20480',
        ]);

        $dynamicData = $request->filled('dynamic_data') ? json_decode($request->dynamic_data, true) : [];
        $mediaPaths = $incident->media_paths ?? [];

        // 1. Procesar eliminación de archivos existentes
        if ($request->filled('archivos_a_eliminar')) {
            $aEliminar = json_decode($request->archivos_a_eliminar, true);
            foreach ($aEliminar as $campoNombre) {
                if (isset($mediaPaths[$campoNombre])) {
                    // Extraemos la ruta relativa para borrarla del disco
                    $pathRelativo = str_replace('/storage/', '', $mediaPaths[$campoNombre]);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($pathRelativo);
                    // Lo quitamos de la base de datos
                    unset($mediaPaths[$campoNombre]);
                }
            }
        }

        // 2. Procesar nuevos archivos subidos (sobrescriben si tienen el mismo nombre de campo)
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $campoNombre => $archivo) {
                $path = $archivo->store('sucesos', 'public');
                $mediaPaths[$campoNombre] = '/storage/' . $path;
            }
        }

        $incident->update([
            'toll_id' => $request->toll_id,
            'incident_type' => $request->incident_type,
            'dynamic_data' => $dynamicData,
            'media_paths' => $mediaPaths
        ]);

        return response()->json(['message' => 'Suceso actualizado correctamente', 'data' => $incident]);
    }

    // Método para Archivar (Soft Delete)
    public function destroy($id)
    {
        $incident = Incident::findOrFail($id);
        $incident->delete(); // Esto no lo borra, solo llena la columna 'deleted_at'
        return response()->json(['message' => 'Suceso archivado correctamente']);
    }
}