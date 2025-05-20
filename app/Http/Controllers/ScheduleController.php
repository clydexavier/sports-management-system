<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Event;
use App\Services\ChallongeService;




use App\Http\Requests\ScheduleRequests\StoreScheduleRequest;
use App\Http\Requests\ScheduleRequests\ShowScheduleRequest;
use App\Http\Requests\ScheduleRequests\UpdateScheduleRequest;
use App\Http\Requests\ScheduleRequests\DestroyScheduleRequest;



class ScheduleController extends Controller
{
    protected $challonge;

    public function __construct(ChallongeService $challonge)
    {
        $this->challonge = $challonge;
    }
    //
    public function index(Request $request, $intrams_id, $event_id)
    {
        // First, get the event to obtain the Challonge event ID
        $event = Event::where('id', $event_id)
            ->where('intrams_id', $intrams_id)
            ->firstOrFail();
        
        // Get all local schedules
        $localSchedules = Schedule::where('intrams_id', $intrams_id)
            ->where('event_id', $event_id)
            ->get()
            ->keyBy('match_id'); // Index by match_id for easy lookup
        
        // Fetch latest data from Challonge
        $challongeMatches = $this->challonge->getMatches($event->challonge_event_id);
        
        // Create a map of match_id to suggested_play_order
        $playOrderMap = [];
        foreach ($challongeMatches as $challongeMatchData) {
            $match = $challongeMatchData['match'] ?? $challongeMatchData;
            $matchId = $match['id'];
            // Store the suggested_play_order for this match
            if (isset($match['suggested_play_order'])) {
                $playOrderMap[$matchId] = (int) $match['suggested_play_order'];
            }
        }
        
        // Fetch participants for mapping IDs to names
        $participants = $this->challonge->getTournamentParticipants($event->challonge_event_id);
        $participantMap = collect($participants)->mapWithKeys(function ($item) {
            $participant = $item['participant'] ?? $item;
            return [$participant['id'] => $participant['name']];
        });
        
        // Process each match from Challonge
        foreach ($challongeMatches as $challongeMatchData) {
            $match = $challongeMatchData['match'] ?? $challongeMatchData;
            $matchId = $match['id'];
            
            // Parse scores if present
            $scoresCsv = $match['scores_csv'] ?? null;
            $winner_id = $match['winner_id'] ?? null;
            $is_completed = !empty($winner_id);
            
            // Calculate simple scores if scores_csv is present
            $score_team1 = null;
            $score_team2 = null;
            
            if ($scoresCsv) {
                // For simple scoring, just count the overall score
                // For set-based scoring, count sets won by each team
                $totalTeam1 = 0;
                $totalTeam2 = 0;
                
                $sets = explode(',', $scoresCsv);
                foreach ($sets as $set) {
                    $setScores = explode('-', $set);
                    if (count($setScores) === 2) {
                        if ((int)$setScores[0] > (int)$setScores[1]) {
                            $totalTeam1++;
                        } else if ((int)$setScores[1] > (int)$setScores[0]) {
                            $totalTeam2++;
                        }
                    }
                }
                
                $score_team1 = $totalTeam1;
                $score_team2 = $totalTeam2;
            }
            
            // Get participant names and IDs, ensuring we don't have null values
            $team1_id = $match['player1_id'] ?? 0; // Use 0 as default instead of null
            $team2_id = $match['player2_id'] ?? 0; // Use 0 as default instead of null
            
            // Determine player1 name with W/L notation if needed
            $team1_name = $participantMap[$team1_id] ?? null;
            if (!$team1_name && isset($match['player1_prereq_match_id'])) {
                $prereqOrder = $playOrderMap[$match['player1_prereq_match_id']] ?? null;
                if ($prereqOrder) {
                    $prefix = isset($match['player1_is_prereq_match_loser']) && $match['player1_is_prereq_match_loser'] ? 'L' : 'W';
                    $team1_name = "{$prefix}{$prereqOrder}";
                } else {
                    $team1_name = 'TBD';
                }
            } else if (!$team1_name) {
                $team1_name = 'TBD';
            }
            
            // Determine player2 name with W/L notation if needed
            $team2_name = $participantMap[$team2_id] ?? null;
            if (!$team2_name && isset($match['player2_prereq_match_id'])) {
                $prereqOrder = $playOrderMap[$match['player2_prereq_match_id']] ?? null;
                if ($prereqOrder) {
                    $prefix = isset($match['player2_is_prereq_match_loser']) && $match['player2_is_prereq_match_loser'] ? 'L' : 'W';
                    $team2_name = "{$prefix}{$prereqOrder}";
                } else {
                    $team2_name = 'TBD';
                }
            } else if (!$team2_name) {
                $team2_name = 'TBD';
            }
            
            // Update or create local record
            if (isset($localSchedules[$matchId])) {
                // Update existing schedule
                $localSchedules[$matchId]->update([
                    'team_1' => $match['player1_id'] ?? 0,
                    'team_2' => $match['player2_id'] ?? 0,
                    'team1_name' => $team1_name,
                    'team2_name' => $team2_name,
                    'scores_csv' => $scoresCsv,
                    'score_team1' => $score_team1,
                    'score_team2' => $score_team2,
                    'winner_id' => $winner_id,
                    'is_completed' => $is_completed,
                ]);
            } else {
                // Create new schedule if not exists
                Schedule::create([
                    'match_id' => $matchId,
                    'challonge_event_id' => $event->challonge_event_id,
                    'event_id' => $event_id,
                    'intrams_id' => $intrams_id,
                    'team_1' => $match['player1_id'] ?? 0,
                    'team_2' => $match['player2_id'] ?? 0,
                    'team1_name' => $team1_name,
                    'team2_name' => $team2_name,
                    'scores_csv' => $scoresCsv,
                    'score_team1' => $score_team1,
                    'score_team2' => $score_team2,
                    'winner_id' => $winner_id,
                    'is_completed' => $is_completed,
                    'date' => null,
                    'time' => null,
                    'venue' => null,
                ]);
            }
        }
        
        // Get updated schedules
        $updatedSchedules = Schedule::where('intrams_id', $intrams_id)
            ->where('event_id', $event_id)
            ->get();
        
        // Sort the schedules based on the play order map
        if (!empty($playOrderMap)) {
            $updatedSchedules = $updatedSchedules->sort(function ($a, $b) use ($playOrderMap) {
                $orderA = $playOrderMap[$a->match_id] ?? PHP_INT_MAX; // Default to max value if not found
                $orderB = $playOrderMap[$b->match_id] ?? PHP_INT_MAX;
                return $orderA <=> $orderB; // PHP 7+ spaceship operator for comparison
            })->values(); // Reindex the array after sorting
        }
        
        return response()->json($updatedSchedules, 200);
    }
    public function store(StoreScheduleRequest $request)
    {
        $validated = $request->validated();

        $schedule = Schedule::create($validated);
        
        return response()->json($schedule, 201);
    }

    public function show(ShowScheduleRequest $request)
    {
        $validated = $request->validated();
        $schedule = Schedule::where('id', $validated['id'])->where('event_id', $validated['event_id'])->firstOrFail();

        return response()->json($schedule, 200);
    }

    public function update(UpdateScheduleRequest $request) 
    {
        $validated = $request->validated();
        
        $schedule = Schedule::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();

        $schedule->update($validated);
        
        return response()->json($schedule, 200);

    }

    public function destroy (DestroyScheduleRequest $request)
    {
        $validated = $request->validated();

        $schedule = Schedule::where('id', $validated['id'])->where('event_id', $validated['event_id'])->firstOrFail();


        $schedule->delete();
        return response()->json(200);
    }


}