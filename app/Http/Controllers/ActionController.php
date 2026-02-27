<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActionController extends Controller
{
    public function index(Request $request)
    {
        $query = Action::with('toll')->latest();
        
        if ($request->boolean('archived')) {
            $query->onlyTrashed();
        }


        return response()->json($query->paginate(15));
    }

    public function show($id)
    {
        $action = Action::withTrashed()->with('toll')->findOrFail($id);
        return response()->json($action);
    }

    public function store(Request $request)
    {
        $request->validate([
            'toll_id' => 'nullable|exists:tolls,id',
            'category' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,mp4,pdf|max:20480',
        ]);

        $mediaPaths = [];
        $archivosMedia = $request->file('media');

        if (!empty($archivosMedia) && is_array($archivosMedia)) {
            foreach ($archivosMedia as $archivo) {
                $path = $archivo->store('acciones', 'public');
                $mediaPaths[] = '/storage/' . $path;
            }
        }

        $action = Action::create([
            'toll_id' => $request->toll_id,
            'category' => $request->category,
            'title' => $request->title,
            'description' => $request->description,
            'media_paths' => $mediaPaths
        ]);

        return response()->json(['message' => 'Acción registrada exitosamente', 'data' => $action], 201);
    }

    public function update(Request $request, $id)
    {
        $action = Action::findOrFail($id);

        $request->validate([
            'toll_id' => 'nullable|exists:tolls,id',
            'category' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,mp4,pdf|max:20480',
        ]);

        $mediaPaths = $action->media_paths ?? [];

     
        if ($request->filled('archivos_a_eliminar')) {
            $aEliminar = json_decode($request->archivos_a_eliminar, true);
            foreach ($aEliminar as $ruta) {
                $pathRelativo = str_replace('/storage/', '', $ruta);
                Storage::disk('public')->delete($pathRelativo);
                $mediaPaths = array_values(array_filter($mediaPaths, fn($p) => $p !== $ruta));
            }
        }

      
        $archivosMedia = $request->file('media');
        if (!empty($archivosMedia) && is_array($archivosMedia)) {
            foreach ($archivosMedia as $archivo) {
                $path = $archivo->store('acciones', 'public');
                $mediaPaths[] = '/storage/' . $path;
            }
        }

        $action->toll_id = $request->toll_id;
        $action->category = $request->category;
        $action->title = $request->title;
        $action->description = $request->description;
        $action->media_paths = $mediaPaths;
        $action->save();

        return response()->json(['message' => 'Acción actualizada correctamente', 'data' => $action]);
    }

    public function destroy($id)
    {
        $action = Action::findOrFail($id);
        $action->delete();
        return response()->json(['message' => 'Acción archivada correctamente']);
    }
}