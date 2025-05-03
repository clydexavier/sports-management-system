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

    public function tally(Request $request, string $id)
    {
        // Get all podiums for this intramural, along with team relationships
        $podiums = Podium::with(['gold', 'silver', 'bronze'])
            ->where('intrams_id', $id)
            ->get();

        $tally = [];

        foreach ($podiums as $podium) {
            $event = $podium->event;
            if (!$event) continue;

            // Event-specific medal count (can be 0, 1, or more)
            $goldCount = $event->gold ?? 0;
            $silverCount = $event->silver ?? 0;
            $bronzeCount = $event->bronze ?? 0;

            // Helper to tally medals
            $addMedal = function ($team, $medal, $count = 1) use (&$tally) {
                if (!$team) return;

                $teamId = $team->id;

                if (!isset($tally[$teamId])) {
                    $tally[$teamId] = [
                        'team_id' => $teamId,
                        'team_name' => $team->name,
                        'team_logo' => $team->team_logo_path ? asset('storage/' . $team->team_logo_path) : null,
                        'gold' => 0,
                        'silver' => 0,
                        'bronze' => 0,
                    ];
                }

                $tally[$teamId][$medal] += $count;
            };

            $addMedal($podium->gold, 'gold', $goldCount);
            $addMedal($podium->silver, 'silver', $silverCount);
            $addMedal($podium->bronze, 'bronze', $bronzeCount);
        }

        // Convert to collection for sorting
        $sorted = collect($tally)->sort(function ($a, $b) {
            // Sort by gold DESC, silver DESC, bronze DESC
            return [$b['gold'], $b['silver'], $b['bronze']] <=> [$a['gold'], $a['silver'], $a['bronze']];
        })->values();

        return response()->json([
            'data' => $sorted
        ], 200);
    }

}