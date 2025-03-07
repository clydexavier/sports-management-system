<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\EventRequests\StoreEventRequest;
use App\Http\Requests\EventRequests\ShowEventRequest;
use App\Http\Requests\EventRequests\UpdateEventRequest;
use App\Http\Requests\EventRequests\DestroyEventRequest;

use App\Models\Event;

class EventController extends Controller
{
    //
    public function index(string $intrams_id) 
    {
        $events = Event::where('intrams_id', $intrams_id)->get();
        return response()->json($events, 200);
    }

    public function store(StoreEventRequest $request) 
    {
        $validated = $request->validated();

        $event = Event::create($validated);

        return response()->json($event, 201);
    }

    public function show(ShowEventRequest $request) 
    {
        $validated = $request->validated();
        $event = Event::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();
        return response()->json($event, 200);
    }

    public function update(UpdateEventRequest $request) 
    {
        $validated = $request->validated();

        $event = Event::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();
        $event->update($validated);
        return response()->json(['message' => 'Event updated successfully', 'event' => $event], 200);
    }

    public function destroy(DestroyEventRequest $request) 
    {
        $validated = $request-> validated();
        $event = Event::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();
        $event->delete();
        return response()->json(['message' => 'Event deleted successfully.'], 204);
    }
}
