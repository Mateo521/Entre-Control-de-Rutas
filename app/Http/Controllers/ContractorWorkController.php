<?php

namespace App\Http\Controllers;

use App\Models\ContractorWork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractorWorkController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\ContractorWork::query();

       
        if ($request->boolean('archived')) {
            $query->onlyTrashed();
        }

       
        return response()->json($query->latest()->paginate(15));
    }

    public function update(Request $request, $id)
    {
       
        $work = \App\Models\ContractorWork::withTrashed()->findOrFail($id);

        $request->validate([
            'company_name' => 'required|string',
            'work_type' => 'required|string',
            'location' => 'required|string',
            'status' => 'required|string',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:20480',
        ]);

       
        $existingMediaPaths = $work->media_paths ?? [];
        $newMediaPaths = [];

      
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $path = $file->store('pasteros', 'public');
                $newMediaPaths[] = '/storage/' . $path;
            }
        }

       
        $combinedMediaPaths = array_merge($existingMediaPaths, $newMediaPaths);

        $work->update([
            'company_name' => $request->company_name,
            'work_type' => $request->work_type,
            'location' => $request->location,
            'description' => $request->description,
            'status' => $request->status,
            'media_paths' => empty($combinedMediaPaths) ? null : $combinedMediaPaths,
        ]);

        return response()->json($work, 200);
    }

    public function destroy($id)
    {
     
        $work = \App\Models\ContractorWork::findOrFail($id);
        $work->delete();
        return response()->json(['message' => 'Trabajo archivado.'], 200);
    }

    public function restore($id)
    {
        
        $work = \App\Models\ContractorWork::withTrashed()->findOrFail($id);
        $work->restore();
        return response()->json(['message' => 'Trabajo restaurado.'], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string',
            'work_type' => 'required|string',
            'location' => 'required|string',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,mp4,pdf|max:20480',
        ]);

        $mediaPaths = [];
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $archivo) {
                $path = $archivo->store('pasteros', 'public');
                $mediaPaths[] = '/storage/' . $path;
            }
        }

        $work = ContractorWork::create([
            'company_name' => $request->company_name,
            'work_type' => $request->work_type,
            'location' => $request->location,
            'description' => $request->description,
            'status' => $request->status ?? 'En Progreso',
            'media_paths' => $mediaPaths
        ]);

        return response()->json(['message' => 'Trabajo tercerizado registrado', 'data' => $work], 201);
    }
}