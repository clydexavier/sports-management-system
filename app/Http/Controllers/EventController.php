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

        $status = $request->query('status');
        $search = $request->query('search');

        $query = Event::where('intrams_id', $intrams_id);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
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

            $challongeTournaments = [];
            // Fetch Challonge tournaments
            /*$params = [
                'state' => $request->query('state', 'all'),
                'type' => $request->query('type', 'single_elimination')
            ];
            foreach ($events as $event) {
                if ($event->challonge_event_id) {
                    $challongeTournaments[] = $this->challonge->getTournament($event->challonge_event_id, $params);
                }
            }*/
    }

    /**
     * Create an event and sync it with Challonge.
     */
    public function store(StoreEventRequest $request)
    {
        $validated = $request->validated();

        // Create tournament in Challonge
        $challongeParams = [
            'name' => $validated['category']. " " .$validated['name'],
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
                'name' => $event->category . " " . $event->name,
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
        
        if (isset($startResponse['tournament'])) {
            $event->status = 'in progress';
            $event->save();
        }
        return response()->json($startResponse);
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