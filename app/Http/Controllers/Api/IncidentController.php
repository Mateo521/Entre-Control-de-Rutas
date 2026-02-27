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
        $query = Incident::with('toll')->latest();

        // Filtros del módulo de Búsqueda Avanzada
        if ($request->filled('toll_id')) {
            $query->where('toll_id', $request->toll_id);
        }
        
        if ($request->filled('incident_type')) {
            $query->where('incident_type', $request->incident_type);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filtros heredados de otras vistas (si los conservas)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('archived')) {
            $query->onlyTrashed();
        }

        return response()->json($query->paginate(15));
    }

    public function show($id)
    {

        $incident = Incident::withTrashed()->with('toll')->findOrFail($id);
        return response()->json($incident);
    }

    public function store(Request $request)
    {
        $request->validate([
            'toll_id' => 'required|exists:tolls,id',
            'incident_type' => 'required|string',
            'dynamic_data' => 'nullable|string',
            'media.*.*' => 'nullable|file|mimes:jpg,jpeg,png,mp4,pdf|max:20480',
        ]);

        $dynamicData = $request->filled('dynamic_data') ? json_decode($request->dynamic_data, true) : [];
        $mediaPaths = [];

        // CORRECCIÓN: Extraemos los archivos directamente sin usar hasFile()
        $archivosMedia = $request->file('media');

        if (!empty($archivosMedia) && is_array($archivosMedia)) {
            foreach ($archivosMedia as $campoNombre => $archivos) {
                $mediaPaths[$campoNombre] = [];
                foreach ($archivos as $archivo) {
                    $path = $archivo->store('sucesos', 'public');
                    $mediaPaths[$campoNombre][] = '/storage/' . $path;
                }
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
            'media.*.*' => 'nullable|file|mimes:jpg,jpeg,png,mp4,pdf|max:20480',
        ]);

        $dynamicData = $request->filled('dynamic_data') ? json_decode($request->dynamic_data, true) : [];

        $mediaPaths = $incident->media_paths;
        if (is_string($mediaPaths)) {
            $mediaPaths = json_decode($mediaPaths, true);
        }
        $mediaPaths = $mediaPaths ?: [];

        foreach ($mediaPaths as $key => $value) {
            if (!is_array($value)) {
                $mediaPaths[$key] = [$value];
            }
        }

        if ($request->filled('archivos_a_eliminar')) {
            $aEliminar = json_decode($request->archivos_a_eliminar, true);
            foreach ($aEliminar as $campoNombre => $rutasParaBorrar) {
                if (isset($mediaPaths[$campoNombre])) {
                    foreach ($rutasParaBorrar as $ruta) {
                        $pathRelativo = str_replace('/storage/', '', $ruta);
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($pathRelativo);
                        $mediaPaths[$campoNombre] = array_values(array_filter($mediaPaths[$campoNombre], fn($p) => $p !== $ruta));
                    }
                    if (empty($mediaPaths[$campoNombre])) {
                        unset($mediaPaths[$campoNombre]);
                    }
                }
            }
        }

        // CORRECCIÓN: Extraemos los archivos directamente sin usar hasFile()
        $archivosMedia = $request->file('media');

        if (!empty($archivosMedia) && is_array($archivosMedia)) {
            foreach ($archivosMedia as $campoNombre => $archivos) {
                if (!isset($mediaPaths[$campoNombre])) {
                    $mediaPaths[$campoNombre] = [];
                }
                foreach ($archivos as $archivo) {
                    $path = $archivo->store('sucesos', 'public');
                    $mediaPaths[$campoNombre][] = '/storage/' . $path;
                }
            }
        }

        $incident->toll_id = $request->toll_id;
        $incident->incident_type = $request->incident_type;
        $incident->dynamic_data = $dynamicData;
        $incident->media_paths = $mediaPaths;

        $incident->save();

        return response()->json([
            'message' => 'Suceso actualizado correctamente',
            'data' => $incident
        ]);
    }



    public function destroy($id)
    {
        $incident = Incident::findOrFail($id);
        $incident->delete();
        return response()->json(['message' => 'Suceso archivado correctamente']);
    }
}