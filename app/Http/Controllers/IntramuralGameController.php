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
        $intramural = IntramuralGame::with(['teams', 'podiums.event', 'podiums.gold', 'podiums.silver', 'podiums.bronze'])->findOrFail($id);

        $tally = [];

        // Step 1: Initialize all teams with zero medals
        foreach ($intramural->teams as $team) {
            $tally[$team->id] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'team_logo' => $team->team_logo_path ? asset('storage/' . $team->team_logo_path) : null,
                'gold' => 0,
                'silver' => 0,
                'bronze' => 0,
            ];
        }

        // Step 2: Loop through podiums and count medals based on event medal allocations
        foreach ($intramural->podiums as $podium) {
            $event = $podium->event;
            if (!$event) continue;
            
            // Skip this event if it doesn't match the type filter
            if ($type !== 'overall' && strtolower($event->type) !== strtolower($type)) {
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
                        'team_logo' => $team->team_logo_path ? asset('storage/' . $team->team_logo_path) : null,
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
                        'team_logo' => $team->team_logo_path ? asset('storage/' . $team->team_logo_path) : null,
                        'gold' => 0,
                        'silver' => 0,
                        'bronze' => 0,
                    ];
                }
            }
        }

        // Step 3: Sort the tally by gold, silver, then bronze
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
        $events = Event::where('intrams_id', $intrams_id)
            ->get(['id', 'name', 'category'])   // get only the fields we need
            ->map(function ($event) {
                return [
                    'id'   => $event->id,
                    'name' => $event->category . ' ' . $event->name,
                ];
            });

        return response()->json($events, 200);
    }


}