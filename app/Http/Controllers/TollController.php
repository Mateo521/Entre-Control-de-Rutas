<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Toll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class TollController extends Controller
{
    public function index()
    {
        $tolls = Toll::latest()->paginate(15);
        return response()->json($tolls);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'dynamic_schema' => 'nullable|array',
            'dynamic_schema.inventory_fields.*.name' => 'required_with:dynamic_schema.inventory_fields|string',
            'dynamic_schema.inventory_fields.*.type' => 'required_with:dynamic_schema.inventory_fields|string',
        ]);

        $toll = Toll::create($validatedData);

        return response()->json([
            'message' => 'Peaje creado exitosamente',
            'data' => $toll
        ], 201);
    }

    public function show($id)
    {
        $toll = Toll::find($id);

        if (!$toll) {
            return response()->json(['message' => 'Peaje no encontrado'], 404);
        }

        return response()->json($toll, 200);
    }

    public function update(Request $request, $id)
    {
        $toll = Toll::find($id);

        if (!$toll) {
            return response()->json(['message' => 'Peaje no encontrado'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'dynamic_schema' => 'nullable|array',
        ]);

        $toll->update($validatedData);

        return response()->json([
            'message' => 'Peaje actualizado exitosamente',
            'data' => $toll
        ], 200);
    }

 
    public function uploadImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',  
        ]);

        $toll = Toll::findOrFail($id);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
           
            $filename = 'peaje_' . $toll->id . '_' . time() . '.jpg';
            $destinationPath = public_path('img/peajes');
 
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
 
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);
 
            $image->scaleDown(width: 800);
             
            $image->toJpeg(quality: 70)->save($destinationPath . '/' . $filename);
 
            if ($toll->image_path && File::exists(public_path($toll->image_path))) {
                File::delete(public_path($toll->image_path));
            }

          
            $toll->update(['image_path' => '/img/peajes/' . $filename]);

            return response()->json([
                'message' => 'Imagen procesada y guardada con éxito',
                'image_path' => $toll->image_path
            ]);
        }

        return response()->json(['message' => 'No se recibió ninguna imagen'], 400);
    }

    public function destroy($id)
    {
        $toll = Toll::find($id);

        if (!$toll) {
            return response()->json(['message' => 'Peaje no encontrado'], 404);
        }

        $toll->delete();

        return response()->json(['message' => 'Peaje archivado exitosamente'], 200);
    }
}