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
    public function index(string $intrams_id) {
        //
        $venues = Venue::where('intrams_id', $intrams_id)->get();
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
    public function store(Request $request, string $intrams_id)
    {
        //
        $validated = $request->validate([
            'name' => ['required'],
            'location' => ['required'],
            'type' => ['required'],
        ]);
    
        $intramural = IntramuralGame::findOrFail($intrams_id);
        $venue = Venue::create([
            'name' => $validated['name'],
            'location' => $validated['location'],
            'type' => $validated['type'],
            'intrams_id' => $intrams_id,
        ]);
        return response()->json($venue, 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $intrams_id, string $id)
    {
        // Find the venue with the given ID and ensure it belongs to the correct intrams_id
        $venue = Venue::where('id', $id)
                    ->where('intrams_id', $intrams_id)
                    ->firstOrFail();

        return response()->json($venue, 200);
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
    public function update(Request $request, string $intrams_id, string $id)
    {
        //
        $validated = $request->validate([
            'name' => ['sometimes'],
            'location' => ['sometimes'],
            'type' => ['sometimes'], //outdoor or indoor
        ]);
        $venue = Venue::where('id', $id)
                    ->where('intrams_id', $intrams_id)
                    ->firstOrFail();

        $venue->update($validated);
        return response()->json(['message' =>'Venue updated successfully', 'Venue' => $venue], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $intrams_id,string $id)
    {
        //
        $venue = Venue::where('id', $id)
                    ->where('intrams_id', $intrams_id)
                    ->firstOrFail();
        $venue->delete();
        return response()->json(['message' => 'venue deleted successfully.'], 204);       
    }
}
