<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IntramuralGame;

class IntramuralGameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        try {
            return response()->json(IntramuralGame::all(), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            "name" => "required",
            'year' => ['required', 'digits:4', 'integer', 'min:2000'],
        ]);

        $intramural = IntramuralGame::create([
            'name' => $request->name,
            'year' => $request->year,
        ]);

        return response()->json($intramural, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $game = IntramuralGame::findOrFail($id);
        return response()->json($game);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $request->validate([
            'name' => ['required', 'string', 'max: 100'],
            'date' => ['required', 'date'],
        ]);

        $game = IntramuralGame::findOrFail($id);
        $game->update([
            'name' => $request->input('name'),
            'date' => $request->input('date'),
        ]);

        return response()->json(['message' =>'Game updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $game = IntramuralGame::findOrFail($id);
        $game->delete();
        return response()->json(null, 204);
    }
}
