<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Models\IntramuralGame;
//use App\Http\Controllers\IntramuralGameController;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $venues = Venue::all();
        return response()->json($venues, 200);
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
        $validated = $request->validate([
            'name' => ['required'],
            'location' => ['required'],
            'type' => ['required'],
            'intrams_id' => ['required', 'exists:intramural_games,id']
        ]);
    
        $venue = Venue::create([
            'name' => $request->name,
            'location' => $request->location,
            'type' => $request->type,
            'intrams_id' => $request->intrams_id,
        ]);
        return response()->json($venue, 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        
        $venue = Venue::findOrFail($id);
        return response()->json($venue);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Venue $venue)
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
            'name' => ['required'],
            'location' => ['required'],
            'type' => ['required'], //outdoor or indoor
            'intrams_id' => ['required', 'exists:intramural_games,id']
        ]);

        $venue = Venue::findOrFail(id);
        $venue->update([
            'name' => $request->input('name'),
            'location' => $request->input('location'),
            'type' => $request->input('type'),
            'intrams_id' => $request->input('intrams_id'),
        ]);

        return response()->json(['message' =>'Venue updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        try {
            $venue = Venue::findOrFail($id);
            $venue->delete();
            return response()->json(['message' => 'venue deleted successfully.'], 204);    
        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'venue not found'], 404);
        }
    }
}
