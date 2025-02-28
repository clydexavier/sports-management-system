<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venue;
use App\Models\Event;

class EventController extends Controller
{
    //
    public function index(string $intrams_id, string $venue_id) 
    {
        $events = Event::where('venue_id', $venue_id)->get();
        return response()->json($events, 200);
    }

    public function store(Request $request,string $intrams_id, string $venue_id) 
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:50'],
            'golds' => ['required', 'integer', 'min:0'],
            'silver' => ['required', 'integer', 'min:0'],
            'bronze' => ['required', 'integer', 'min:0'],
        ]);

        $venue = Venue::where('venue_id', $venue_id)->where('intrams_id', $intrams_id);

        $event = Event::create([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'golds' => $validated['golds'],
            'silver' => $validated['silver'],
            'bronze' => $validated['bronze'],
            'venue_id' => $venue_id,
        ]);

        return response()->json($event, 201);
    }

    public function show(string $intrams_id, string $venue_id, string $id) 
    {
        $event = Event::where('id', $id)->where('venue_id', $venue_id)->firstOrFail();
        return response()->json($event, 200);
    }

    public function update(Request $request, string $intrams_id ,string $venue_id, string $id) 
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'category' => ['sometimes', 'string', 'max:50'],
            'golds' => ['sometimes', 'integer', 'min:0'],
            'silver' => ['sometimes', 'integer', 'min:0'],
            'bronze' => ['sometimes', 'integer', 'min:0'],
        ]);

        $event = Event::where('id', $id)->where('venue_id', $venue_id)->firstOrFail();
        $event->update($validated);
        return response()->json(['message' => 'Event info updated successfully', 'event' => $event], 200);
    }

    public function destroy(string $intrams_id, string $venue_id, string $id) 
    {
        $event = Event::where('id', $id)->where('venue_id', $venue_id)->firstOrFail();
        $event->delete();
        return response()->json(['message' => 'Venue deleted successfully.'], 204);
    }
}
