<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ChallongeService;

use App\Models\Event;



class GameController extends Controller
{
    protected $challonge;

    public function __construct(ChallongeService $challonge)
    {
        $this->challonge = $challonge;
    }
    //
    public function index(Request $request, string $intrams_id, string $event_id)
    {
        $perPage = 12;
        $page = (int) $request->query('page', 1);

        $event = Event::where('intrams_id', $intrams_id)
            ->where('id', $event_id)
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



}