<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TowService;
use Illuminate\Http\Request;

class TowServiceController extends Controller
{
   public function index(Request $request)
    {
        $query = TowService::query();

    
        if ($request->boolean('archived')) {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('license_plate', 'ILIKE', "%{$search}%")
                  ->orWhere('location', 'ILIKE', "%{$search}%");
        }

        if ($request->filled('tow_truck')) {
            $query->where('tow_truck', $request->input('tow_truck'));
        }

        $services = $query->latest()->paginate(15);

        return response()->json($services);
    }
    
    public function restore($id)
    {
        $service = TowService::withTrashed()->findOrFail($id);
        $service->restore();

        return response()->json(['message' => 'Registro de acarreo restaurado exitosamente.'], 200);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tow_truck' => 'required|string|in:Grúa Provincial,Grúa Desaguadero,Grúa La Cumbre',
            'license_plate' => 'required|string|max:20',
            'location' => 'required|string|max:255',
            'reason' => 'required|string|max:255',
            'observations' => 'nullable|string'
        ]);

  
        $validated['license_plate'] = strtoupper($validated['license_plate']);

        $service = TowService::create($validated);

        return response()->json($service, 201);
    }

    public function update(Request $request, $id)
    {
        $service = TowService::findOrFail($id);

        $validated = $request->validate([
            'tow_truck' => 'required|string|in:Grúa Provincial,Grúa Desaguadero,Grúa La Cumbre',
            'license_plate' => 'required|string|max:20',
            'location' => 'required|string|max:255',
            'reason' => 'required|string|max:255',
            'observations' => 'nullable|string'
        ]);

        $validated['license_plate'] = strtoupper($validated['license_plate']);

        $service->update($validated);

        return response()->json($service, 200);
    }

    public function destroy($id)
    {
        $service = TowService::findOrFail($id);
        $service->delete();

        return response()->json(['message' => 'Registro de acarreo eliminado (archivado) correctamente.'], 200);
    }
}