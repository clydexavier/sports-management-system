<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IntramuralGame;
use App\Models\Event;
use App\Models\Schedule;
use App\Models\Podium;
use App\Models\OverallTeam;
use App\Services\ChallongeService;

class GuestController extends Controller
{
    protected $challonge;

    public function __construct(ChallongeService $challonge)
    {
        $this->challonge = $challonge;
    }

    /**
     * Get all active intramurals
     */
    public function getIntramurals(Request $request)
    {
        $intramurals = IntramuralGame::where('status', '!=', 'pending')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'location', 'status', 'start_date', 'end_date']);

        
        if(count($intramurals)< 1 ) {
            return response()->json([
                'data' => $intramurals,
                'message' => 'No active intramurals.'
            ], 200);

        }

        return response()->json([
            'data' => $intramurals,
            'message' => 'Active intramurals retrieved successfully'
        ], 200);
    }

    /**
     * Get intramural details
     */
    public function getIntramuralDetails(string $intrams_id)
    {
        $intramural = IntramuralGame::where('id', $intrams_id)
            ->where('status', '!=', 'pending')
            ->first(['id', 'name', 'location', 'status', 'start_date', 'end_date']);

        if (!$intramural) {
            return response()->json(['message' => 'Intramural not found or not publicly available'], 404);
        }

        return response()->json($intramural, 200);
    }

    /**
     * Get overall medal tally for an intramural
     */
    public function getOverallTally(Request $request, string $intrams_id)
    {
        $type = $request->query('type', 'overall');
        
        $intramural = IntramuralGame::with([
            'teams', 
            'podiums.event', 
            'podiums.gold', 
            'podiums.silver', 
            'podiums.bronze',
            'podiums.event.parent'
        ])->where('id', $intrams_id)
          ->where('status', '!=', 'pending')
          ->first();

        if (!$intramural) {
            return response()->json(['message' => 'Intramural not found or not publicly available'], 404);
        }

        $tally = [];

        // Initialize all teams with zero medals
        foreach ($intramural->teams as $team) {
            $tally[$team->id] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'team_logo_path' => $team->team_logo_path,
                'gold' => 0,
                'silver' => 0,
                'bronze' => 0,
            ];
        }

        // Identify dependent umbrella events
        $dependentUmbrellaIds = Event::where('intrams_id', $intrams_id)
                                ->where('is_umbrella', true)
                                ->where('has_independent_medaling', false)
                                ->pluck('id')
                                ->toArray();

        // Process podiums for medal counting
        foreach ($intramural->podiums as $podium) {
            $event = $podium->event;
            if (!$event) continue;
            
            // Skip if doesn't match type filter
            if ($type !== 'overall' && strtolower($event->type) !== strtolower($type)) {
                continue;
            }

            // Skip sub-events of dependent medaling umbrella events
            if ($event->parent_id && in_array($event->parent_id, $dependentUmbrellaIds)) {
                continue;
            }

            // Skip umbrella events with independent medaling
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
                        'team_logo_path' => $team->team_logo_path,
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

        // Sort by medals
        $sorted = collect($tally)->sort(function ($a, $b) {
            return [$b['gold'], $b['silver'], $b['bronze']] <=> [$a['gold'], $a['silver'], $a['bronze']];
        })->values();

        return response()->json([
            'data' => $sorted,
            'intrams_name' => $intramural->name,
        ], 200);
    }

    /**
     * Get all podium results for an intramural
     */
    public function getPodiumResults(Request $request, string $intrams_id)
    {
        $intramural = IntramuralGame::where('id', $intrams_id)
            ->where('status', '!=', 'pending')
            ->first();

        if (!$intramural) {
            return response()->json(['message' => 'Intramural not found or not publicly available'], 404);
        }

        $type = $request->query('type', 'all');
        $search = $request->query('search', '');

        $query = Podium::with([
            'event',
            'gold',
            'silver',
            'bronze',
        ])->where('intrams_id', $intrams_id);

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

        $podiums = $query->orderBy('created_at', 'desc')->get();

        $data = $podiums->map(function ($podium) {
            $event = $podium->event;
            $isSubEvent = $event->parent_id !== null;
            $parentName = $isSubEvent ? $event->parent->name : null;
            
            return [
                'id' => $podium->id,
                'event_id' => $event->id,
                'event' => [
                    'name' => $event->category . ' ' . $event->name,
                    'type' => $event->type,
                    'is_umbrella' => $event->is_umbrella,
                    'has_independent_medaling' => $event->has_independent_medaling,
                    'is_sub_event' => $isSubEvent,
                    'parent_name' => $parentName
                ],
                'gold_team_logo' => $podium->gold?->team_logo_path ?? null,
                'gold_team_name' => $podium->gold?->name ?? 'N/A',
                'silver_team_logo' => $podium->silver?->team_logo_path ?? null,
                'silver_team_name' => $podium->silver?->name ?? 'N/A',
                'bronze_team_logo' => $podium->bronze?->team_logo_path ?? null,
                'bronze_team_name' => $podium->bronze?->name ?? 'N/A',
                'medals' => $event->gold,
            ];
        });

        return response()->json([
            'data' => $data,
            'intrams_name' => $intramural->name,
        ], 200);
    }

    /**
     * Get events for an intramural
     */
    public function getEvents(Request $request, string $intrams_id)
    {
        $intramural = IntramuralGame::where('id', $intrams_id)
            ->where('status', '!=', 'pending')
            ->first();

        if (!$intramural) {
            return response()->json(['message' => 'Intramural not found or not publicly available'], 404);
        }

        $type = $request->query('type', 'all');
        $search = $request->query('search', '');

        $query = Event::where('intrams_id', $intrams_id)
            ->where('status', '!=', 'pending'); // Only show started or completed events

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $events = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'category' => $event->category,
                    'type' => $event->type,
                    'status' => $event->status,
                    'is_umbrella' => $event->is_umbrella,
                    'parent_id' => $event->parent_id,
                    'created_at' => $event->created_at,
                ];
            }),
            'intrams_name' => $intramural->name,
        ], 200);
    }

    /**
     * Get event details
     */
    public function getEventDetails(string $intrams_id, string $event_id)
    {
        $event = Event::where('id', $event_id)
            ->where('intrams_id', $intrams_id)
            ->where('status', '!=', 'pending')
            ->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found or not publicly available'], 404);
        }

        return response()->json([
            'id' => $event->id,
            'name' => $event->name,
            'category' => $event->category,
            'type' => $event->type,
            'status' => $event->status,
            'tournament_type' => $event->tournament_type,
            'is_umbrella' => $event->is_umbrella,
            'parent_id' => $event->parent_id,
            'gold' => $event->gold,
            'silver' => $event->silver,
            'bronze' => $event->bronze,
        ], 200);
    }

    /**
     * Get event bracket
     */
    public function getEventBracket(string $intrams_id, string $event_id)
    {
        $event = Event::where('id', $event_id)
            ->where('intrams_id', $intrams_id)
            ->where('status', '!=', 'pending')
            ->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found or not publicly available'], 404);
        }

        if ($event->tournament_type === "no bracket") {
            return response()->json("no bracket", 200);
        }

        if (!$event->challonge_event_id) {
            return response()->json(['message' => 'Bracket not available'], 404);
        }

        try {
            $challongeTournament = $this->challonge->getTournament($event->challonge_event_id);
            $tournamentUrl = $challongeTournament['tournament']['url'] ?? null;

            if (!$tournamentUrl) {
                return response()->json(['message' => 'Tournament URL not found'], 404);
            }

            return response()->json([
                'bracket_id' => $tournamentUrl, 
                'status' => $event->status
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unable to fetch bracket'], 500);
        }
    }

    /**
     * Get event matches
     */
    public function getEventMatches(Request $request, string $intrams_id, string $event_id)
    {
        $event = Event::where('id', $event_id)
            ->where('intrams_id', $intrams_id)
            ->where('status', '!=', 'pending')
            ->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found or not publicly available'], 404);
        }

        if ($event->tournament_type === "no bracket") {
            return response()->json(['message' => 'This event has no bracket'], 200);
        }

        $page = (int) $request->query('page', 1);
        $perPage = 12;

        try {
            $allMatches = $this->challonge->getMatches($event->challonge_event_id);
            
            if (!is_array($allMatches)) {
                return response()->json(['message' => 'Failed to retrieve matches'], 500);
            }

            $matches = collect($allMatches)->map(fn($item) => $item['match'] ?? $item);
            $playOrderMap = $matches->pluck('suggested_play_order', 'id')->all();

            $participants = $this->challonge->getTournamentParticipants($event->challonge_event_id);
            $participantMap = collect($participants)->mapWithKeys(function ($item) {
                $participant = $item['participant'] ?? $item;
                return [$participant['id'] => $participant['name']];
            });

            $total = $matches->count();
            $sortedMatches = $matches->sortBy(function($match) {
                return (int) $match['suggested_play_order'];
            })->values();
            
            $paginatedMatches = $sortedMatches->slice(($page - 1) * $perPage, $perPage)->values();

            $data = $paginatedMatches->map(function ($match) use ($participantMap, $playOrderMap) {
                // Determine player names
                $player1_name = $participantMap[$match['player1_id']] ?? null;
                if (!$player1_name && $match['player1_prereq_match_id']) {
                    $prereqOrder = $playOrderMap[$match['player1_prereq_match_id']] ?? null;
                    if ($prereqOrder) {
                        $prefix = $match['player1_is_prereq_match_loser'] ? 'L' : 'W';
                        $player1_name = "{$prefix}{$prereqOrder}";
                    }
                }

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
                    'state' => $match['state'],
                    'player1_name' => $player1_name ?? 'TBD',
                    'player2_name' => $player2_name ?? 'TBD',
                    'round' => $match['round'],
                    'suggested_play_order' => $match['suggested_play_order'],
                    'scores_csv' => $match['scores_csv'] ?? null,
                    'winner_id' => $match['winner_id'] ?? null,
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
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Unable to fetch matches'], 500);
        }
    }

    /**
     * Get event schedule
     */
    public function getEventSchedule(string $intrams_id, string $event_id)
    {
        $event = Event::where('id', $event_id)
            ->where('intrams_id', $intrams_id)
            ->where('status', '!=', 'pending')
            ->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found or not publicly available'], 404);
        }

        if ($event->tournament_type === "no bracket") {
            return response()->json(['message' => 'This event has no bracket'], 200);
        }

        $schedules = Schedule::where('intrams_id', $intrams_id)
            ->where('event_id', $event_id)
            ->orderBy('suggested_play_order')
            ->get();

        return response()->json($schedules, 200);
    }

    /**
     * Get event podium result
     */
    public function getEventPodium(string $intrams_id, string $event_id)
    {
        $event = Event::where('id', $event_id)
            ->where('intrams_id', $intrams_id)
            ->where('status', 'completed') // Only show podium for completed events
            ->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found or not completed'], 404);
        }

        $podium = Podium::where('event_id', $event_id)
            ->where('intrams_id', $intrams_id)
            ->with(['gold', 'silver', 'bronze'])
            ->first();

        if (!$podium) {
            return response()->json(['message' => 'Podium result not available'], 404);
        }

        return response()->json([
            'id' => $podium->id,
            'event_id' => $podium->event_id,
            'gold_team_id' => $podium->gold_team_id,
            'gold_team_name' => $podium->gold?->name,
            'gold_team_logo' => $podium->gold?->team_logo_path,
            'silver_team_id' => $podium->silver_team_id,
            'silver_team_name' => $podium->silver?->name,
            'silver_team_logo' => $podium->silver?->team_logo_path,
            'bronze_team_id' => $podium->bronze_team_id,
            'bronze_team_name' => $podium->bronze?->name,
            'bronze_team_logo' => $podium->bronze?->team_logo_path,
            'created_at' => $podium->created_at,
        ], 200);
    }

    /**
     * Get event standings
     */
    public function getEventStandings(string $intrams_id, string $event_id)
    {
        $event = Event::where('id', $event_id)
            ->where('intrams_id', $intrams_id)
            ->where('status', '!=', 'pending')
            ->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found or not publicly available'], 404);
        }

        if ($event->tournament_type === "no bracket" || !$event->challonge_event_id) {
            return response()->json(['message' => 'Standings not available for this event type'], 404);
        }

        try {
            $response = $this->challonge->getParticipantStandings($event->challonge_event_id);
            
            $standings = collect($response)->map(function($item) {
                $participant = $item['participant'] ?? $item;
                
                return [
                    'id' => $participant['id'],
                    'name' => $participant['name'],
                    'seed' => $participant['seed'],
                    'final_rank' => $participant['final_rank'] ?? null,
                    'wins' => $participant['wins'] ?? 0,
                    'losses' => $participant['losses'] ?? 0,
                ];
            })->sortBy(function($participant) {
                return $participant['final_rank'] ?? $participant['seed'];
            })->values();
            
            return response()->json($standings, 200);
            
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unable to fetch standings'], 500);
        }
    }
}