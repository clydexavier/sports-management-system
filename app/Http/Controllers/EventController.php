<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ChallongeService;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\EventRequests\StoreEventRequest;
use App\Http\Requests\EventRequests\ShowEventRequest;
use App\Http\Requests\EventRequests\UpdateEventRequest;
use App\Http\Requests\EventRequests\DestroyEventRequest;
use App\Http\Requests\EventRequests\StartEventRequest;
use App\Http\Requests\EventRequests\FinalizeEventRequest;
use App\Http\Requests\EventRequests\ResetEventRequest;

use App\Models\Event;
use App\Models\Schedule;
use App\Models\IntramuralGame;



class EventController extends Controller
{
    protected $challonge;

    public function __construct(ChallongeService $challonge)
    {
        $this->challonge = $challonge;
    }

    /**
     * Retrieve all events linked to an intramural.
     */
    public function index(string $intrams_id, Request $request)
    {
        $perPage = 12;

        $type = $request->query('type');
        $search = $request->query('search');

        $query = Event::where('intrams_id', $intrams_id);

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $events = $query->orderBy('created_at', 'desc')->paginate($perPage);


       

        return response()->json([
            'data' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ]
        ], 200);
    }

    public function event_status(Request $request, string $intrams_id, string $id) 
    {
        $event = Event::where('id', $id)->where('intrams_id', $intrams_id)->firstOrFail();
        
        $data = [
            'status' => $event->status,
            'tournament_type' => $event->tournament_type, 
        ];
        return response()->json($data, 200);

    }

    /**
     * Create an event and sync it with Challonge.
     */
    public function store(StoreEventRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = "pending";

        // Create tournament in Challonge
        $challongeParams = [
            'name' => IntramuralGame::find($validated['intrams_id'])->name ." " .$validated['category']. " " .$validated['name'],
            'tournament_type' => $validated['tournament_type'],
            'hold_third_place_match' => $validated['hold_third_place_match'] ?? true,
            'show_rounds' => true
        ];
        $challongeResponse = $this->challonge->createTournament($challongeParams);

        // Extract the Challonge tournament ID
        $challongeEventId = $challongeResponse['tournament']['id'] ?? null;

        $this->challonge->getTournament($challongeEventId);

        // Create event in our database with the Challonge event ID
        $validated['challonge_event_id'] = $challongeEventId;
        $event = Event::create($validated);

        return response()->json($event, 201);
    }
    /**
     * Show event details (including optional Challonge tournament data).
     */
    public function show(ShowEventRequest $request)
    {
        $validated = $request->validated();

        $event = Event::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();

        // Fetch from Challonge
        $params = [
            'include_participants' => $request->query('include_participants', false),
            'include_matches' => $request->query('include_matches', false)
        ];
        $challongeTournament = $this->challonge->getTournament($event->challonge_event_id, $params);
        return response()->json($challongeTournament['tournament']['name'], 200);
    }

    public function bracket(Request $request, string $intrams_id, string $event_id)
    {
        $event = Event::where('id', $event_id)
            ->where('intrams_id', $intrams_id)
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
     * Update event and sync changes with Challonge.
     */
    public function update(UpdateEventRequest $request)
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {
            $event = Event::where('id', $validated['id'])
                ->where('intrams_id', $validated['intrams_id'])
                ->firstOrFail();

            // Temporarily update the model in memory but don't save yet
            $event->fill($validated);

            // Prepare Challonge update
            
            $challongeParams = [
                'name' => IntramuralGame::find($validated['intrams_id'])->name ." " .$validated['category']. " " .$validated['name'],
                'tournament_type' => $event->tournament_type
            ];

            // Call Challonge API first
            $challongeResponse = $this->challonge->updateTournament($event->challonge_event_id, $challongeParams);

            // Only if Challonge API succeeds, proceed with saving to DB
            $event->save();

            return response()->json([
                'message' => 'Event updated successfully',
                'event' => $event,
                'challonge_tournament' => $challongeResponse
            ], 200);
        });
    }

    /**
     * Delete an event and remove it from Challonge.
     */
    public function destroy(DestroyEventRequest $request)
    {
        $validated = $request->validated();

        $event = Event::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();

        // Delete from Challonge
        $this->challonge->deleteTournament($event->challonge_event_id);
        $event->delete();
        return response()->json(['message' => 'Event deleted successfully.'], 204);
    }

    /**
     * Start an event/tournament.
     */
    public function start(StartEventRequest $request)
    {
        $validated = $request->validated();
       
        $event = Event::where('id', $validated['id'])
            ->where('intrams_id', $validated['intrams_id'])
            ->firstOrFail();
       
        $participantsInput = $request->input('participants', []);
       
        if (empty($participantsInput)) {
            return response()->json(['message' => 'Participants data is required.'], 422);
        }

        // Wrap in expected Challonge format
        $payload = [
            'api_key' => env('CHALLONGE_API_KEY'),
            'participants' => $participantsInput,
        ];
        // Send to Challonge
        $addResponse = $this->challonge->addTournamentParticipants($event->challonge_event_id, $payload);
        
        // Check if participants were added successfully
        if (empty($addResponse) || !isset($addResponse[0]['participant'])) {
            return response()->json(['message' => 'Failed to add participants to tournament.'], 500);
        }

        // Start the tournament
        $startResponse = $this->challonge->startTournament($event->challonge_event_id);
        
        if (!isset($startResponse['tournament'])) {
            return response()->json(['message' => 'Failed to start tournament.'], 500);
        }
    
        $event->status = 'in progress';
        $event->save();

        // Fetch matches
        $allMatches = $this->challonge->getMatches($event->challonge_event_id);
        $matches = collect($allMatches)->map(fn($item) => $item['match'] ?? $item);
        $playOrderMap = $matches->pluck('suggested_play_order', 'id')->all();

        // Fetch participants
        $participants = $this->challonge->getTournamentParticipants($event->challonge_event_id);
        $participantMap = collect($participants)->mapWithKeys(function ($item) {
            $participant = $item['participant'] ?? $item;
            return [$participant['id'] => $participant['name']];
        });

        // Resolve player names
        $resolvePlayerName = function ($match, $key, $prereqKey, $isLoserKey) use ($participantMap, $playOrderMap) {
            $id = $match[$key];
            if (isset($participantMap[$id])) {
                return $participantMap[$id];
            }

            $prereqId = $match[$prereqKey] ?? null;
            if ($prereqId && isset($playOrderMap[$prereqId])) {
                $prefix = $match[$isLoserKey] ? 'L' : 'W';
                return "{$prefix}{$playOrderMap[$prereqId]}";
            }

            return 'TBD';
        };

        // Create schedule entries for each match
        foreach ($matches as $match) {
            $scheduleData = [
                'match_id' => (string) $match['id'],
                'challonge_event_id' => $event->challonge_event_id,
                'event_id' => $event->id,
                'intrams_id' => $event->intrams_id,
                'team_1' => (string) ($match['player1_id'] ?? '0'),
                'team_2' => (string) ($match['player2_id'] ?? '0'),
                'team1_name' => $resolvePlayerName($match, 'player1_id', 'player1_prereq_match_id', 'player1_is_prereq_match_loser'),
                'team2_name' => $resolvePlayerName($match, 'player2_id', 'player2_prereq_match_id', 'player2_is_prereq_match_loser'),
                'date' => null,
                'time' => null,
            ];
            // Store schedule directly via model (bypassing request validation)
            Schedule::create($scheduleData);
        }

        return response()->json($startResponse);
    }

    /**
     * Submit scores for a match
     */
    public function submitScore(Request $request, string $intrams_id, string $event_id, string $match_id)
    {
        \Log::info('Incoming data:', $request->all());

        // Validate request - making scores optional
        $validated = $request->validate([
            'score_team1' => 'nullable|integer|min:0',
            'score_team2' => 'nullable|integer|min:0',
            'scores_csv' => 'nullable|string', // For set-based scoring
            'winner_id' => 'required|string'
        ]);
        
        // Get event to verify it exists
        $event = Event::where('id', $event_id)
            ->where('intrams_id', $intrams_id)
            ->firstOrFail();
        
        // Find the schedule/match
        $schedule = Schedule::where('match_id', $match_id)
            ->where('event_id', $event_id)
            ->firstOrFail();
        
        // Update in our database
        $scheduleData = [
            'winner_id' => $validated['winner_id'],
            'is_completed' => true,
            'score_team1' => 0,
            'score_team2' => 0,
        ];

        
        // Only include scores if provided
        if (isset($validated['score_team1'])) {
            $scheduleData['score_team1'] = $validated['score_team1'];
        }
        
        if (isset($validated['score_team2'])) {
            $scheduleData['score_team2'] = $validated['score_team2'];
        }
        
        if (isset($validated['scores_csv'])) {
            $scheduleData['scores_csv'] = $validated['scores_csv'];
        }
        // Prepare data for Challonge
        $challongeParams = [
            'winner_id' => $validated['winner_id']
        ];
        
        // Add scores_csv if available (for set-based games)
        if (isset($validated['scores_csv']) && !empty($validated['scores_csv'])) {
            $challongeParams['scores_csv'] = $validated['scores_csv'];
        } 
        // If no set scores but regular scores are available, format them as scores_csv
        elseif (isset($validated['score_team1']) && isset($validated['score_team2'])) {
            $challongeParams['scores_csv'] = $validated['score_team1'] . '-' . $validated['score_team2'];
        }
        
        // Update in Challonge
        $response = $this->challonge->updateMatchScore($event->challonge_event_id, $match_id, $challongeParams);
        
        if (isset($response['match'])) {
            $schedule->update($scheduleData);
            return response()->json([
                'message' => 'Match result updated successfully',
                'schedule' => $schedule,
                'challonge_response' => $response
            ], 200);
        }
        return response()->json($response);

        //return response()->json(['message' => 'Failed to submit match result. Please try again.'], 500);
        
    }


    public function getStandings(string $intrams_id, string $event_id)
    {
        $event = Event::where('id', $event_id)
            ->where('intrams_id', $intrams_id)
            ->firstOrFail();
        
        // Get standings from Challonge
        $response = $this->challonge->getParticipantStandings($event->challonge_event_id);
        
        // Process the response to get a clean standings array
        $standings = collect($response)->map(function($item) {
            $participant = $item['participant'] ?? $item;
            
            // Create base participant data
            $result = [
                'id' => $participant['id'],
                'name' => $participant['name'],
                'seed' => $participant['seed'],
                'final_rank' => $participant['final_rank'] ?? null,
                'wins' => $participant['wins'] ?? 0,
                'losses' => $participant['losses'] ?? 0,
            ];
            
            // Add scoring data if available
            if (isset($participant['scores_for'])) {
                $result['scores_for'] = $participant['scores_for'];
            }
            
            if (isset($participant['scores_against'])) {
                $result['scores_against'] = $participant['scores_against'];
            }
            
            // Add match history if available
            if (isset($participant['matches'])) {
                $result['matches'] = collect($participant['matches'])->map(function($match) {
                    return [
                        'id' => $match['id'],
                        'round' => $match['round'],
                        'opponent_id' => $match['opponent_id'],
                        'winner_id' => $match['winner_id'],
                        'scores_csv' => $match['scores_csv'] ?? null
                    ];
                })->toArray();
            }
            
            return $result;
        })->sortBy(function($participant) {
            // Sort by final_rank if available, otherwise by seed
            return $participant['final_rank'] ?? $participant['seed'];
        })->values();
        
        return response()->json($standings, 200);
    }


    /**
     * Finalize an event/tournament.
     */
    public function finalize(FinalizeEventRequest $request)
    {
        $event = Event::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();

        $response = $this->challonge->finalizeTournament($event->challonge_event_id);
        // Check if the Challonge response indicates a successful start
        if (isset($response['tournament'])) {
            // Update the event status
            $event->status = 'completed';
            $event->save();
        }
        return response()->json($response);
    }

    /**
     * Reset an event/tournament.
     */
    public function reset(ResetEventRequest $request)
    {
        $validated = $request->validated();
        $event = Event::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();
        $response = $this->challonge->resetTournament($event->challonge_event_id);

        // Check if the Challonge response indicates a successful start
        if (isset($response['tournament'])) {
            // Update the event status
            $event->status = 'pending';
            $event->save();
        }
        return response()->json($response);
    }
}