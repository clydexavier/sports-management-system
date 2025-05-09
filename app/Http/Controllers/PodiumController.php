<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PodiumRequests\StorePodiumRequest;
use App\Http\Requests\PodiumRequests\ShowPodiumRequest;
use App\Http\Requests\PodiumRequests\UpdatePodiumRequest;
use App\Http\Requests\PodiumRequests\DestroyPodiumRequest;
use Illuminate\Support\Facades\Storage;

use App\Models\Podium;
use App\Models\OverallTeam;
use App\Models\Event;


class PodiumController extends Controller
{
    //

    public function index(Request $request, string $intrams_id)
    {
        \Log::info('Incoming data: ', $request->all());
        $perPage = 12;

        $type = $request->query('type');
        $search = $request->query('search');

        $query = Podium::with([
            'event',
            'gold',
            'silver',
            'bronze',
        ])->where('intrams_id', $intrams_id);

        // Filter by event type (from events table)
        if ($type && $type !== 'all') {
            $query->whereHas('event', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        // Search by event name
        if ($search) {
            $query->whereHas('event', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        $podiums = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $podiums->map(function ($podium) {
            return [
                'event' => [
                    'name' => $podium->event->category . ' ' . $podium->event->name,
                    'type' => $podium->event->type,
                ],
                'gold_team_logo' => $podium->gold?->team_logo_path 
                    ? asset('storage/' . $podium->gold->team_logo_path) 
                    : null,
                'gold_team_name' => $podium->gold->name,    
                'silver_team_logo' => $podium->silver?->team_logo_path 
                    ? asset('storage/' . $podium->silver->team_logo_path) 
                    : null,
                'silver_team_name' => $podium->silver->name,
                'bronze_team_logo' => $podium->bronze?->team_logo_path 
                    ? asset('storage/' . $podium->bronze->team_logo_path) 
                    : null,
                'bronze_team_name' => $podium->bronze->name,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $podiums->currentPage(),
                'per_page' => $podiums->perPage(),
                'total' => $podiums->total(),
                'last_page' => $podiums->lastPage(),
            ]
        ], 200);
    }


    
    public function store(StorePodiumRequest $request) 
    {
        $validated = $request->validated();
        $podium = Podium::create($validated);
        
        $event = Event::find($validated['event_id']);
        if ($event) {
            $event->status = 'completed';
            $event->save();
        }
        return response()->json($podium, 201);
    }

    public function show(ShowPodiumRequest $request)
    {   
        $validated = $request->validated();
        $podium = Podium::where('event_id', $validated['event_id'])->firstOrFail();

        $gold_team = OverallTeam::find($podium->gold_team_id);
        $silver_team = OverallTeam::find($podium->silver_team_id);
        $bronze_team = OverallTeam::find($podium->bronze_team_id);

        
        return response()->json([
            'id' => $podium->id,
            'intrams_id' => $podium->intrams_id,
            'event_id' => $podium->event_id,
            'gold_team_id' => $podium->gold_team_id,
            'gold_team_name' => $gold_team?->name,
            'silver_team_id' => $podium->silver_team_id,
            'silver_team_name' => $silver_team?->name,
            'bronze_team_id' => $podium->bronze_team_id,
            'bronze_team_name' => $bronze_team?->name,
            'created_at' => $podium->created_at,
            'updated_at' => $podium->updated_at,
        ]);
    }

    public function update(UpdatePodiumRequest $request)
{
    $validated = $request->validated();

    $podium = Podium::where('event_id', $validated['event_id'])->firstOrFail();

    $podium->update($validated);

    // Update the related event status to "completed"
    $event = Event::find($validated['event_id']);
    if ($event) {
        $event->status = 'completed';
        $event->save();
    }

    return response()->json($podium, 200);
}


    public function destroy(DestroyPodiumRequest $request)
    {
        $validated = $request->validated();
        $podium = Podium::where('event_id', $validated['event_id'])->firstOrFail();
        $podium->delete();

        return response()->json(204);
    }

    
}