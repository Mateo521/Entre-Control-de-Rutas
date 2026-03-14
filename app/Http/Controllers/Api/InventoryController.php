<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{

    public function index()
    {
        return response()->json(InventoryItem::orderBy('category')->get());
    }


    public function storeMovement(Request $request)
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01',
            'type' => 'required|in:in,out,adjustment',
            'reference_document' => 'required|string'
        ]);

        DB::transaction(function () use ($request) {

            InventoryMovement::create([
                'inventory_item_id' => $request->inventory_item_id,
                'user_id' => auth()->id() ?? 1,
                'type' => $request->type,
                'quantity' => $request->quantity,
                'reference_document' => $request->reference_document,
                'description' => $request->description,
            ]);


            $item = InventoryItem::findOrFail($request->inventory_item_id);
            if ($request->type === 'in') {
                $item->current_stock += $request->quantity;
            } else {
                $item->current_stock -= $request->quantity;
            }
            $item->save();
        });

        return response()->json(['message' => 'Movimiento registrado correctamente']);
    }

    public function store(\Illuminate\Http\Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'unit_measure' => 'required|string|max:50',
            'alert_threshold' => 'required|numeric|min:0',
            'current_stock' => 'required|numeric|min:0',
        ]);


        $item = \App\Models\InventoryItem::create([
            'name' => $request->name,
            'category' => $request->category,
            'unit_measure' => $request->unit_measure,
            'alert_threshold' => $request->alert_threshold,
            'current_stock' => $request->current_stock,
        ]);


        return response()->json($item, 201);
    }

    public function update(\Illuminate\Http\Request $request, $id)
    {

        $item = \App\Models\InventoryItem::findOrFail($id);


        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'unit_measure' => 'required|string|max:50',
            'alert_threshold' => 'required|numeric|min:0',
        ]);


        $item->update([
            'name' => $request->name,
            'category' => $request->category,
            'unit_measure' => $request->unit_measure,
            'alert_threshold' => $request->alert_threshold,
        ]);

        return response()->json($item, 200);
    }


    public function destroy($id)
    {
        $item = \App\Models\InventoryItem::findOrFail($id);


        $item->delete();

        return response()->json(['message' => 'Insumo eliminado correctamente'], 200);
    }
}