<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

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

    public function restore($id)
    {
        $action = Action::withTrashed()->findOrFail($id);
        $action->restore();
        
        return response()->json(['message' => 'Acción restaurada correctamente'], 200);
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

        // --- BLOQUE DE COMPRESIÓN AÑADIDO AQUÍ ---
        if (!empty($archivosMedia) && is_array($archivosMedia)) {
            foreach ($archivosMedia as $archivo) {
                $extension = strtolower($archivo->getClientOriginalExtension());

                if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $filename = Str::random(40) . '.jpg';
                    $destinationPath = storage_path('app/public/acciones');

                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    $manager = new ImageManager(new Driver());
                    $manager->read($archivo)
                        ->scaleDown(width: 1024)
                        ->toJpeg(quality: 75)
                        ->save($destinationPath . '/' . $filename);

                    $mediaPaths[] = '/storage/acciones/' . $filename;
                } else {
                    $path = $archivo->store('acciones', 'public');
                    $mediaPaths[] = '/storage/' . $path;
                }
            }
        }

        $action = DB::transaction(function () use ($request, $mediaPaths) {
            $newAction = Action::create([
                'toll_id' => $request->toll_id,
                'category' => $request->category,
                'title' => $request->title,
                'description' => $request->description,
                'media_paths' => $mediaPaths
            ]);

            if ($request->has('materiales')) {
                $materiales = json_decode($request->materiales, true);

                if (is_array($materiales)) {
                    foreach ($materiales as $mat) {
                        $newAction->inventoryItems()->attach($mat['inventory_item_id'], [
                            'quantity_used' => $mat['quantity']
                        ]);

                        $item = InventoryItem::find($mat['inventory_item_id']);
                        if ($item) {
                            $item->current_stock -= $mat['quantity'];
                            $item->save();
                        }
                    }
                }
            }

            return $newAction;
        });

        return response()->json(['message' => 'Acción registrada exitosamente', 'data' => $action], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'toll_id' => 'nullable|exists:tolls,id',
            'category' => 'nullable|string',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,mp4,pdf|max:20480',
        ]);

        $action = DB::transaction(function () use ($request, $id) {
            $actionToUpdate = Action::with('inventoryItems')->findOrFail($id);

            if ($request->has('materiales')) {
                foreach ($actionToUpdate->inventoryItems as $existingItem) {
                    $itemModel = InventoryItem::find($existingItem->id);
                    if ($itemModel) {
                        $itemModel->current_stock += $existingItem->pivot->quantity_used;
                        $itemModel->save();
                    }
                }

                $actionToUpdate->inventoryItems()->detach();

                $materiales = json_decode($request->materiales, true);
                if (is_array($materiales)) {
                    foreach ($materiales as $mat) {
                        $actionToUpdate->inventoryItems()->attach($mat['inventory_item_id'], [
                            'quantity_used' => $mat['quantity']
                        ]);

                        $itemModel = InventoryItem::find($mat['inventory_item_id']);
                        if ($itemModel) {
                            $itemModel->current_stock -= $mat['quantity'];
                            $itemModel->save();
                        }
                    }
                }
            }

            $actionToUpdate->title = $request->title;
            $actionToUpdate->description = $request->description;

            if ($request->has('category')) {
                $actionToUpdate->category = $request->category;
            }
            if ($request->has('toll_id')) {
                $actionToUpdate->toll_id = $request->toll_id;
            }

            $mediaPaths = $actionToUpdate->media_paths ?? [];

            if ($request->has('archivos_a_eliminar')) {
                $aEliminar = json_decode($request->archivos_a_eliminar, true);
                if (is_array($aEliminar)) {
                    $mediaPaths = array_filter($mediaPaths, function ($path) use ($aEliminar) {
                        return !in_array($path, $aEliminar);
                    });
                }
            }

            $archivosMedia = $request->file('media');
            
            // --- BLOQUE DE COMPRESIÓN AÑADIDO AQUÍ TAMBIÉN ---
            if (!empty($archivosMedia) && is_array($archivosMedia)) {
                foreach ($archivosMedia as $archivo) {
                    $extension = strtolower($archivo->getClientOriginalExtension());

                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $filename = Str::random(40) . '.jpg';
                        $destinationPath = storage_path('app/public/acciones');

                        if (!file_exists($destinationPath)) {
                            mkdir($destinationPath, 0755, true);
                        }

                        $manager = new ImageManager(new Driver());
                        $manager->read($archivo)
                            ->scaleDown(width: 1024)
                            ->toJpeg(quality: 75)
                            ->save($destinationPath . '/' . $filename);

                        $mediaPaths[] = '/storage/acciones/' . $filename;
                    } else {
                        $path = $archivo->store('acciones', 'public');
                        $mediaPaths[] = '/storage/' . $path;
                    }
                }
            }

            $actionToUpdate->media_paths = array_values($mediaPaths);
            $actionToUpdate->save();

            return $actionToUpdate;
        });

        return response()->json(['message' => 'Acción actualizada exitosamente', 'data' => $action], 200);
    }

    public function destroy($id)
    {
        $action = Action::findOrFail($id);
        $action->delete();
        return response()->json(['message' => 'Acción archivada correctamente']);
    }
}