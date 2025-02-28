<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests\StoreIntramuralGameRequest;
use App\Http\Requests\UpdateIntramuralGameRequest;
use App\Models\IntramuralGame;

class IntramuralGameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $games = IntramuralGame::all();
        return response()->json($games, 200);
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
    public function store(StoreIntramuralGameRequest $request)
    {
        //
        $validated = $request->validated();
        $intramural = IntramuralGame::create($data);

        return response()->json($intramural, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $game = IntramuralGame::findOrFail($id);
        return response()->json($game, 200);
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
    public function update(UpdateIntramuralGameRequest $request, string $id)
    {
        //
        $validated = $request->validated();
        $game = IntramuralGame::findOrFail($id);
        $game->update($validated);

        return response()->json(['message' =>'Game updated successfully', 'game' => $game], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        try {
            $game = IntramuralGame::findOrFail($id);
            $game->delete();
            return response()->json(['message' => 'intramural game deleted successfully.'], 204);    
        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'intramural game not found'], 404);
        }
        
    }
}
