<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Models\IntramuralGame;
use App\Http\Requests\VenueRequests\StoreVenueRequest;
use App\Http\Requests\VenueRequests\UpdateVenueRequest;

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
     * Store a newly created resource in storage.
     */
    public function store(StoreVenueRequest $request)
    {
        //
        $validated = $request->validated();
        $venue = Venue::create($validated);

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
     * Update the specified resource in storage.
     */
    public function update(UpdateVenueRequest $request)
    {
        //
        $validated = $request->validated();
        $venue = Venue::findOrFail($validated['id']);
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
