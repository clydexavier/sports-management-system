<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests\IntramuralRequests\StoreIntramuralGameRequest;
use App\Http\Requests\IntramuralRequests\UpdateIntramuralGameRequest;
use App\Models\IntramuralGame;

class IntramuralGameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = 12;

        $games = IntramuralGame::paginate($perPage);

        return response()->json([
            'data' => $games->items(),
            'meta' => [
                'current_page' => $games->currentPage(),
                'per_page' => $games->perPage(),
                'total' => $games->total(),
                'last_page' => $games->lastPage(),
            ]
        ], 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIntramuralGameRequest $request)
    {
        //
        $validated = $request->validated();
        $intramural = IntramuralGame::create($validated);

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
     * Update the specified resource in storage.
     */
    public function update(UpdateIntramuralGameRequest $request)
    {
        //
        $validated = $request->validated();
        $game = IntramuralGame::findOrFail($validated['id'])->firstOrFail();
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