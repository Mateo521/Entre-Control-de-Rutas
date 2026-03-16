<?php
namespace App\Http\Controllers\Api;  
use App\Http\Controllers\Controller;
use App\Models\WorkType;
use Illuminate\Http\Request;

class WorkTypeController extends Controller
{
    public function index()
    {
        return response()->json(WorkType::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:work_types,name']);
        $tipo = WorkType::create(['name' => $request->name]);
        return response()->json($tipo, 201);
    }
}