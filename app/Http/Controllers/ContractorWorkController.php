<?php

namespace App\Http\Controllers;

use App\Models\ContractorWork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractorWorkController extends Controller
{
    public function index(Request $request)
    {

        $perPage = $request->input('per_page', 15);

        $trabajos = ContractorWork::latest()->paginate($perPage);

        return response()->json($trabajos);
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