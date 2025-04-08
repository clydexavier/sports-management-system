<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\VenueRequests\StoreVenueRequest;
use App\Http\Requests\VenueRequests\UpdateVenueRequest;
use App\Models\Venue;

class VenueController extends Controller
{
    //
    public function index(string $intrams_id, Request $request) 
    {
        $venues = Venue::where('intrams_id', $intrams_id)->get();
        
        return response()->json($venues, 200);
    }

    public function show() 
    {
        
    }

    public function store(StoreVenueRequest $request) 
    {
        $validated = $request->validated();
        $venue = Venue::create($validated);
        return response()->json($venue, 201);
    }

    public function update(UpdateVenueRequest $request)
    {
        $validated = $request->validated();
        $venue = Venue::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();

        $venue->update($validated);

        return response()->json([
            'message' => 'Venue updated successfully',
            'venue' => $venue
        ], 200);

    }
}