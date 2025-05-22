<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests\IntramuralRequests\StoreIntramuralGameRequest;
use App\Http\Requests\IntramuralRequests\UpdateIntramuralGameRequest;
use App\Models\IntramuralGame;
use App\Models\Podium;
use App\Models\Event;


class IntramuralGameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = 12;
        $status = $request->query('status');
        $search = $request->query('search');

        $query = IntramuralGame::query();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $games = $query->orderBy('created_at', 'desc')->paginate($perPage);

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
        $game = IntramuralGame::findOrFail($validated['id']);
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

    public function overall_tally(Request $request, string $id)
    {
        // Get the type filter from the request, default to 'overall'
        $type = $request->query('type', 'overall');
        
        // Load intramural with teams and all podiums + related event and team data
        $intramural = IntramuralGame::with([
            'teams', 
            'podiums.event', 
            'podiums.gold', 
            'podiums.silver', 
            'podiums.bronze',
            'podiums.event.parent'
        ])->findOrFail($id);

        $tally = [];

        // Step 1: Initialize all teams with zero medals
        foreach ($intramural->teams as $team) {
            $tally[$team->id] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'team_logo_path' => $team->team_logo_path ? $team->team_logo_path : null,
                'gold' => 0,
                'silver' => 0,
                'bronze' => 0,
            ];
        }

        // Step 2: Identify dependent umbrella events to avoid double-counting their sub-events
        $dependentUmbrellaIds = Event::where('intrams_id', $id)
                                ->where('is_umbrella', true)
                                ->where('has_independent_medaling', false)
                                ->pluck('id')
                                ->toArray();

        // Step 3: Loop through podiums and count medals based on event medal allocations
        foreach ($intramural->podiums as $podium) {
            $event = $podium->event;
            if (!$event) continue;
            
            // Skip this event if it doesn't match the type filter
            if ($type !== 'overall' && strtolower($event->type) !== strtolower($type)) {
                continue;
            }

            // Skip sub-events of dependent medaling umbrella events to avoid double counting
            if ($event->parent_id && in_array($event->parent_id, $dependentUmbrellaIds)) {
                continue;
            }

            // Also skip the umbrella event itself if it has independent medaling (we count the sub-events instead)
            if ($event->is_umbrella && $event->has_independent_medaling) {
                continue;
            }

            $goldCount = $event->gold ?? 0;
            $silverCount = $event->silver ?? 0;
            $bronzeCount = $event->bronze ?? 0;

            $addMedal = function ($team, $medal, $count = 1) use (&$tally) {
                if (!$team) return;

                if (!isset($tally[$team->id])) {
                    $tally[$team->id] = [
                        'team_id' => $team->id,
                        'team_name' => $team->name,
                        'team_logo_path' => $team->team_logo_path ? $team->team_logo_path : null,
                        'gold' => 0,
                        'silver' => 0,
                        'bronze' => 0,
                    ];
                }

                $tally[$team->id][$medal] += $count;
            };

            $addMedal($podium->gold, 'gold', $goldCount);
            $addMedal($podium->silver, 'silver', $silverCount);
            $addMedal($podium->bronze, 'bronze', $bronzeCount);
        }

        // Filter out teams with no medals (only if a specific type is selected)
        if ($type !== 'overall') {
            $tally = array_filter($tally, function ($teamTally) {
                return $teamTally['gold'] > 0 || $teamTally['silver'] > 0 || $teamTally['bronze'] > 0;
            });
            
            // If no teams have medals for this category, we'll keep all teams with zero counts
            if (empty($tally)) {
                foreach ($intramural->teams as $team) {
                    $tally[$team->id] = [
                        'team_id' => $team->id,
                        'team_name' => $team->name,
                        'team_logo_path' => $team->team_logo_path ? $team->team_logo_path : null,
                        'gold' => 0,
                        'silver' => 0,
                        'bronze' => 0,
                    ];
                }
            }
        }

        // Step 4: Sort the tally by gold, silver, then bronze
        $sorted = collect($tally)->sort(function ($a, $b) {
            return [$b['gold'], $b['silver'], $b['bronze']] <=> [$a['gold'], $a['silver'], $a['bronze']];
        })->values();

        return response()->json([
            'data' => $sorted,
            'intrams_name' => $intramural->name,
        ], 200);
    }

    public function events(Request $request, string $intrams_id)
    {
        // Get all events
        $events = Event::where('intrams_id', $intrams_id)->get();
        
        // Filter events based on our medal distribution logic
        $filteredEvents = $events->filter(function ($event) {
            // Skip sub-events of dependent medaling umbrella events
            if ($event->parent_id) {
                $parent = Event::find($event->parent_id);
                if ($parent && $parent->is_umbrella && !$parent->has_independent_medaling) {
                    return false;
                }
            }
            
            // Skip umbrella events with independent medaling (we show their sub-events instead)
            if ($event->is_umbrella && $event->has_independent_medaling) {
                return false;
            }
            
            return true;
        });
        
        // Format the events
        $formattedEvents = $filteredEvents->map(function ($event) {
            return [
                'id' => $event->id,
                'name' => $event->category . ' ' . $event->name,
                'is_umbrella' => $event->is_umbrella,
                'has_independent_medaling' => $event->has_independent_medaling
            ];
        });

        return response()->json($formattedEvents, 200);
    }
}