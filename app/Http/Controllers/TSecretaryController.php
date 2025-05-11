<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Game;
use App\Models\Podium;
use App\Models\OverallTeam;
use App\Models\Schedule;
use App\Models\IntramuralGame;


use Illuminate\Support\Facades\Auth;
use App\Services\ChallongeService;


class TSecretaryController extends Controller
{
    protected $challonge;

    public function __construct(ChallongeService $challonge)
    {
        $this->challonge = $challonge;
    }
    //
    /**
     * Get the current user's assigned event details
     */
    public function getCurrentEvent()
    {
        $user = Auth::user();
        
        if (!$user->intrams_id || !$user->event_id) {
            return response()->json(['message' => 'No event assigned'], 404);
        }
        
        $event = Event::where('id', $user->event_id)
                      ->where('intrams_id', $user->intrams_id)
                      ->first();
                      
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }
        
        return response()->json($event);
    }

    /**
     * Get status for the current user's event
     */

    public function getEventStatus()
    {
        $user = Auth::user();
        if (!$user->intrams_id || !$user->event_id) {
            return response()->json(['message' => 'No event assigned'], 404);
        }
        $event = Event::where('id', $user->event_id)->where('intrams_id', $user->intrams_id)->firstOrFail();
        
        return response()->json($event->status);
    }

    /**
     * Get podium for the current user's event
     */
    public function getTeamNames() 
    {
        $user = Auth::user();
        $teams = OverallTeam::where('intrams_id', $user->intrams_id)
                    ->orderBy('created_at', 'desc')
                    ->get(['id', 'name']);
        return response()->json($teams, 200);
    }

    /**
     * Get podium for the current user's event
     */

     public function getPodium()
     {
         $user = Auth::user();
         $podium = Podium::where('event_id', $user->event_id)->firstOrFail();
 
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
    
    /**
     * Get game schedules for the current user's event
     */

    public function getSchedules(Request $request) 
    {
        $user = Auth::user();
        if (!$user->intrams_id || !$user->event_id) {
            return response()->json(['message' => 'No event assigned'], 404);
        }

        $scheds = Schedule::where('intrams_id', $user->intrams_id)->where('event_id', $user->event_id)->get();
        return response()->json($scheds, 200);

    }
    /**
     * Get games for the current user's event
     */
    public function getGames(Request  $request)
    {
        $user = Auth::user();

        $perPage = 12;
        $page = (int) $request->query('page', 1);

        $event = Event::where('intrams_id', $user->intrams_id)
            ->where('id', $user->event_id)
            ->firstOrFail();

        if (!$event->challonge_event_id) {
            return response()->json([
                'message' => 'Not linked to Challonge.'
            ], 404);
        }

        $allMatches = $this->challonge->getMatches($event->challonge_event_id);

        if (!is_array($allMatches)) {
            return response()->json([
                'message' => 'Failed to retrieve matches from Challonge.',
                'raw_response' => $allMatches
            ], 500);
        }
        
        // Normalize matches
        $matches = collect($allMatches)->map(fn($item) => $item['match'] ?? $item);

        // Build a map of match_id => suggested_play_order
        $playOrderMap = $matches->pluck('suggested_play_order', 'id')->all();

        // Get participants
        $participants = $this->challonge->getTournamentParticipants($event->challonge_event_id);
        $participantMap = collect($participants)->mapWithKeys(function ($item) {
            $participant = $item['participant'] ?? $item;
            return [$participant['id'] => $participant['name']];
        });

        // Sort matches
        $total = $matches->count();
        $sortedMatches = $matches->sortBy('suggested_play_order')->values();
        $paginatedMatches = $sortedMatches->slice(($page - 1) * $perPage, $perPage)->values();
        
       // Transform data
       $data = $paginatedMatches->map(function ($match) use ($participantMap, $playOrderMap) {
        // Determine player1 name
        $player1_name = $participantMap[$match['player1_id']] ?? null;
        if (!$player1_name && $match['player1_prereq_match_id']) {
            $prereqOrder = $playOrderMap[$match['player1_prereq_match_id']] ?? null;
            if ($prereqOrder) {
                $prefix = $match['player1_is_prereq_match_loser'] ? 'L' : 'W';
                $player1_name = "{$prefix}{$prereqOrder}";
            }
        }

        // Determine player2 name
        $player2_name = $participantMap[$match['player2_id']] ?? null;
        if (!$player2_name && $match['player2_prereq_match_id']) {
            $prereqOrder = $playOrderMap[$match['player2_prereq_match_id']] ?? null;
            if ($prereqOrder) {
                $prefix = $match['player2_is_prereq_match_loser'] ? 'L' : 'W';
                $player2_name = "{$prefix}{$prereqOrder}";
            }
        }

        return [
            'id' => $match['id'],
            'tournament_id' => $match['tournament_id'],
            'state' => $match['state'],
            'player1_id' => $match['player1_id'],
            'player2_id' => $match['player2_id'],
            'player1_name' => $player1_name ?? 'TBD',
            'player2_name' => $player2_name ?? 'TBD',
            'round' => $match['round'],
            'suggested_play_order' => $match['suggested_play_order'],
        ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ]
        ]);
    }
    
    /**
     * Get specific game
     */
    public function getGame($id)
    {
        $user = Auth::user();

        $perPage = 12;
        $page = (int) $request->query('page', 1);

        $event = Event::where('intrams_id', $user->intrams_id)
            ->where('id', $user->event_id)
            ->firstOrFail();

        if (!$event->challonge_event_id) {
            return response()->json([
                'message' => 'Not linked to Challonge.'
            ], 404);
        }

        $allMatches = $this->challonge->getMatches($event->challonge_event_id);

        if (!is_array($allMatches)) {
            return response()->json([
                'message' => 'Failed to retrieve matches from Challonge.',
                'raw_response' => $allMatches
            ], 500);
        }
        
        // Normalize matches
        $matches = collect($allMatches)->map(fn($item) => $item['match'] ?? $item);

        // Build a map of match_id => suggested_play_order
        $playOrderMap = $matches->pluck('suggested_play_order', 'id')->all();

        // Get participants
        $participants = $this->challonge->getTournamentParticipants($event->challonge_event_id);
        $participantMap = collect($participants)->mapWithKeys(function ($item) {
            $participant = $item['participant'] ?? $item;
            return [$participant['id'] => $participant['name']];
        });

        // Sort matches
        $total = $matches->count();
        $sortedMatches = $matches->sortBy('suggested_play_order')->values();
        $paginatedMatches = $sortedMatches->slice(($page - 1) * $perPage, $perPage)->values();
        
       // Transform data
       $data = $paginatedMatches->map(function ($match) use ($participantMap, $playOrderMap) {
        // Determine player1 name
        $player1_name = $participantMap[$match['player1_id']] ?? null;
        if (!$player1_name && $match['player1_prereq_match_id']) {
            $prereqOrder = $playOrderMap[$match['player1_prereq_match_id']] ?? null;
            if ($prereqOrder) {
                $prefix = $match['player1_is_prereq_match_loser'] ? 'L' : 'W';
                $player1_name = "{$prefix}{$prereqOrder}";
            }
        }

        // Determine player2 name
        $player2_name = $participantMap[$match['player2_id']] ?? null;
        if (!$player2_name && $match['player2_prereq_match_id']) {
            $prereqOrder = $playOrderMap[$match['player2_prereq_match_id']] ?? null;
            if ($prereqOrder) {
                $prefix = $match['player2_is_prereq_match_loser'] ? 'L' : 'W';
                $player2_name = "{$prefix}{$prereqOrder}";
            }
        }

        return [
            'id' => $match['id'],
            'tournament_id' => $match['tournament_id'],
            'state' => $match['state'],
            'player1_id' => $match['player1_id'],
            'player2_id' => $match['player2_id'],
            'player1_name' => $player1_name ?? 'TBD',
            'player2_name' => $player2_name ?? 'TBD',
            'round' => $match['round'],
            'suggested_play_order' => $match['suggested_play_order'],
        ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ]
        ]);
    }
    
    /**
     * Update game details
     */
    public function updateGame(Request $request, $id)
    {
        $user = Auth::user();
        
        $game = Game::where('id', $id)
                    ->where('event_id', $user->event_id)
                    ->where('intrams_id', $user->intrams_id)
                    ->first();
                    
        if (!$game) {
            return response()->json(['message' => 'Game not found'], 404);
        }
        
        // Validate the request
        $validated = $request->validate([
            'venue_id' => 'sometimes|exists:venues,id',
            'scheduled_time' => 'sometimes|date',
            'state' => 'sometimes|string'
        ]);
        
        $game->update($validated);
        
        return response()->json($game);
    }
    
    /**
     * Submit game score
     */
    public function submitScore(Request $request, $id)
    {
        $user = Auth::user();
        
        $game = Game::where('id', $id)
                    ->where('event_id', $user->event_id)
                    ->where('intrams_id', $user->intrams_id)
                    ->first();
                    
        if (!$game) {
            return response()->json(['message' => 'Game not found'], 404);
        }
        
        // Validate the request
        $validated = $request->validate([
            'team1_score' => 'required|integer|min:0',
            'team2_score' => 'required|integer|min:0',
            'winner_id' => 'required|exists:teams,id'
        ]);
        
        $game->update([
            'team1_score' => $validated['team1_score'],
            'team2_score' => $validated['team2_score'],
            'winner_id' => $validated['winner_id'],
            'state' => 'complete'
        ]);
        
        return response()->json($game);
    }
    
    /**
     * Get bracket for current event
     */
    public function getBracket()
    {
        $user = Auth::user();
        $event = Event::where('id', $user->event_id)
            ->firstOrFail();

        // Get full tournament details from Challonge
        $challongeTournament = $this->challonge->getTournament($event->challonge_event_id);

        // Extract the bracket URL (e.g., 'ku8g556h')
        $tournamentUrl = $challongeTournament['tournament']['url'] ?? null;

        if (!$tournamentUrl) {
            return response()->json(['message' => 'Tournament URL not found'], 404);
        }

        return response()->json(['bracket_id' => $tournamentUrl, 'status' => $event->status], 200);
    }
    
    /**
     * Get event result
     */
    public function getEventResult()
    {
        $user = Auth::user();
        
        $event = Event::where('id', $user->event_id)
                      ->where('intrams_id', $user->intrams_id)
                      ->first();
                      
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }
        
        // Get podium data for this event
        $podium = Podium::where('event_id', $user->event_id)
                         ->where('intrams_id', $user->intrams_id)
                         ->with(['goldTeam', 'silverTeam', 'bronzeTeam'])
                         ->first();
                         
        return response()->json([
            'event' => $event,
            'podium' => $podium
        ]);
    }
    

    
    /**
     * Get podiums
     */
    public function getPodiums(Request $request)
    {
        $user = Auth::user();
        \Log::info('Incoming data: ', $request->all());

        $perPage = 12;
        $type = $request->query('type');
        $search = $request->query('search');

        $query = Podium::with([
            'event',
            'gold',
            'silver',
            'bronze',
        ])
        ->where('intrams_id', $user->intrams_id);

        // Filter by event type
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
                'medals' => $podium->event->gold,
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
    
    /**
     * Create podium
     */
    public function createPodium(Request $request)
    {
        $user = Auth::user();
    
        // Validate the request
        $validated = $request->validate([
            'gold_team_id' => 'required|exists:overall_teams,id',
            'silver_team_id' => 'required|exists:overall_teams,id',
            'bronze_team_id' => 'required|exists:overall_teams,id',
        ]);
        
        // Check if podium already exists
        $podium = Podium::where('event_id', $user->event_id)
                         ->where('intrams_id', $user->intrams_id)
                         ->first();
                         
        if ($podium) {
            // Update existing podium
            $podium->update([
                'gold_team_id' => $validated['gold_team_id'],
                'silver_team_id' => $validated['silver_team_id'],
                'bronze_team_id' => $validated['bronze_team_id'],
            ]);
        } else {
            // Create new podium
            $podium = Podium::create([
                'event_id' => $user->event_id,
                'intrams_id' => $user->intrams_id,
                'gold_team_id' => $validated['gold_team_id'],
                'silver_team_id' => $validated['silver_team_id'],
                'bronze_team_id' => $validated['bronze_team_id'],
            ]);
        
        }
        
        $event = Event::find($user->event_id);
        if ($event) {
            $event->status = 'completed';
            $event->save();
        }
        return response()->json($podium, 201);
    }
    
    /**
     * Update podium
     */
    public function updatePodium(Request $request)
    {
        $user = Auth::user();
        
        $podium = Podium::where('event_id', $user->event_id)
                         ->where('intrams_id', $user->intrams_id)
                         ->first();
                         
        if (!$podium) {
            return response()->json(['message' => 'Podium not found'], 404);
        }
        
        // Validate the request
        $validated = $request->validate([
            'gold_team_id' => 'required|exists:overall_teams,id',
            'silver_team_id' => 'required|exists:overall_teams,id',
            'bronze_team_id' => 'required|exists:overall_teams,id',
        ]);
        
        $podium->update($validated);
        // Update the related event status to "completed"
        $event = Event::find($user->event_id);
        if ($event) {
            $event->status = 'completed';
            $event->save();
        }

        return response()->json($podium, 200);
    }
    
    /**
     * Get tally data
     */
    public function getTally()
    {
        $user = Auth::user();
        // Load intramural with teams and all podiums + related event and team data
        $intramural = IntramuralGame::with(['teams', 'podiums.event', 'podiums.gold', 'podiums.silver', 'podiums.bronze'])->findOrFail($user->intrams_id);

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

        // Step 3: Sort the tally by gold, silver, then bronze
        $sorted = collect($tally)->sort(function ($a, $b) {
            return [$b['gold'], $b['silver'], $b['bronze']] <=> [$a['gold'], $a['silver'], $a['bronze']];
        })->values();

        return response()->json([
            'data' => $sorted
        ], 200);   
    }
}