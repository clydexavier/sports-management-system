<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\VenueRequests\StoreVenueRequest;
use App\Http\Requests\VenueRequests\UpdateVenueRequest;
use App\Http\Requests\VenueRequests\DestroyVenueRequest;

use App\Models\Venue;

class VenueController extends Controller
{
    //
    public function index(string $intrams_id, Request $request) 
    {
        $perPage = 12;

        $type = $request->query('type');
        $search = $request->query('search');

        $query = Venue::where('intrams_id', $intrams_id);

        if ($type && $type !== 'All') {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $venues = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $venues->items(),
            'meta' => [
                'current_page' => $venues->currentPage(),
                'per_page' => $venues->perPage(),
                'total' => $venues->total(),
                'last_page' => $venues->lastPage(),
            ]
        ], 200);
        
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

    public function destroy(DestroyVenueRequest $request) 
    {
        $validated = $request->validated();
        $venue = Venue::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();
        $venue->delete();
        
        return response()->json(['message' => 'Event deleted successfully.'], 204);
    }
}