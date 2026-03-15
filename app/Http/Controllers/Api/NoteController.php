<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;
use Carbon\Carbon;  

class NoteController extends Controller
{
  
    public function index(Request $request)
    {
        $query = Note::query();

        if ($request->boolean('archived')) {
            $query->where('is_archived', true);
        } else {
            $query->where('is_archived', false);
        }

     
        $notas = $query->orderByRaw('due_date IS NULL, due_date ASC')->get();

        return response()->json($notas);
    }

  
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $note = Note::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'due_date' => $validated['due_date'],
            'is_completed' => false,
            'is_archived' => false,
        ]);

        return response()->json($note, 201);
    }

   
    public function update(Request $request, Note $note)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $note->update($validated);

        return response()->json($note, 200);
    }

    
    public function changeStatus(Request $request, Note $note)
    {
        $validated = $request->validate([
            'is_completed' => 'boolean',
            'is_archived' => 'boolean',
        ]);

        $note->update($validated);

        return response()->json($note, 200);
    }

   
    public function destroy(Note $note)
    {
        $note->delete();

        return response()->json(['message' => 'Nota eliminada permanentemente.'], 200);
    }

    
    public function alerts()
    {
       
        $limite = Carbon::now()->addDays(3)->toDateString();

        $alertas = Note::where('is_completed', false)  
            ->where('is_archived', false)          
            ->whereNotNull('due_date')              
            ->whereDate('due_date', '<=', $limite)    
            ->orderBy('due_date', 'asc')
            ->get();

        return response()->json($alertas);
    }
}