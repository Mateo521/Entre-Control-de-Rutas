<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Toll;
use App\Models\ContractorWork;
use App\Models\InventoryItem;
use App\Models\Note;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
   public function index()
    {
        $kpis = [
            'sucesos_hoy' => Incident::whereDate('created_at', Carbon::today())->count(),
            'total_sucesos' => Incident::count(),
            'obras_activas' => ContractorWork::where('status', 'En progreso')->count(),
            'alertas_stock' => InventoryItem::whereColumn('current_stock', '<=', 'alert_threshold')->count(),
            'tareas_pendientes' => Note::where('is_completed', false)->where('is_archived', false)->count(),
        ];

        $tipos = Incident::select('incident_type', DB::raw('count(*) as total'))
            ->groupBy('incident_type')
            ->get();

        $peajes = Incident::join('tolls', 'incidents.toll_id', '=', 'tolls.id')
            ->select('tolls.name', DB::raw('count(incidents.id) as total'))
            ->groupBy('tolls.name')
            ->get();

    
        $tendencia = Incident::select(DB::raw('DATE(created_at) as fecha'), DB::raw('count(*) as total'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc')
            ->get();

        $ultimos_sucesos = Incident::with('toll')->latest()->take(5)->get();
        
        $stock_critico = InventoryItem::with('toll')
            ->whereColumn('current_stock', '<=', 'alert_threshold')
            ->orderBy('current_stock', 'asc')
            ->take(5)
            ->get();

        $proximas_notas = Note::where('is_completed', false)
            ->where('is_archived', false)
            ->whereNotNull('due_date')
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        return response()->json([
            'kpis' => $kpis,
            'charts' => [
                'tipos' => $tipos,
                'peajes' => $peajes,
                'tendencia' => $tendencia  
            ],
            'ultimos_sucesos' => $ultimos_sucesos,
            'stock_critico' => $stock_critico,
            'proximas_notas' => $proximas_notas
        ]);
    }
}