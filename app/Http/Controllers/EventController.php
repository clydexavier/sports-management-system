<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venue;
use App\Models\Event;

class EventController extends Controller
{
    //
    public function index(string $venue_id) {
        $events = Event::where('venues_id', $venue_id) -> get();
        return json()->response($events, 200);
    }

    public function store(Request $request, string $venue_id) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:50'],
            'golds' => ['required', 'integer', 'min:0'],
            'silver' => ['required', 'integer', 'min:0'],
            'bronze' => ['required', 'integer', 'min:0'],
        ]);
        $venue = Venue::findOrFail($venue_id);

        $event = Event::create([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'golds' => $validated['golds'],
            'silver' => $validated['silver'],
            'bronze' => $validated['bronze'],
            'venue_id' => $venue_id,
        ]);

        return json() -> response($event, 201);
        }

        public function show(string $venue_id, string $id) {
            $event = Event::where('id', $id)->where('venue_id', $venue_id)->firstOrFail();

            return response()->json($event, 200);
        }

        public function update(Request $request, string $venue_id, string $id) {
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
}
