<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Toll;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. KPIs Generales
        $totalSucesos = Incident::count();
        $sucesosHoy = Incident::whereDate('created_at', today())->count();
        $totalPeajes = Toll::count();

        // 2. Datos para el Gráfico de Anillo (Sucesos por Tipo)
        $sucesosPorTipo = Incident::select('incident_type', DB::raw('count(*) as total'))
            ->groupBy('incident_type')
            ->get();

        // 3. Datos para el Gráfico de Barras (Sucesos por Peaje)
        // Usamos un JOIN para obtener el nombre del peaje en la misma consulta
        $sucesosPorPeaje = Incident::join('tolls', 'incidents.toll_id', '=', 'tolls.id')
            ->select('tolls.name', DB::raw('count(incidents.id) as total'))
            ->groupBy('tolls.id', 'tolls.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // 4. Tabla de Últimos Sucesos (Top 5)
        $ultimosSucesos = Incident::with('toll')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'kpis' => [
                'total_sucesos' => $totalSucesos,
                'sucesos_hoy' => $sucesosHoy,
                'total_peajes' => $totalPeajes,
            ],
            'charts' => [
                'tipos' => $sucesosPorTipo,
                'peajes' => $sucesosPorPeaje
            ],
            'ultimos_sucesos' => $ultimosSucesos
        ]);
    }
}