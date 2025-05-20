<?php

namespace App\Http\Controllers;

use App\Models\IntramuralGame;
use App\Models\Player;
use App\Models\Event;

use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use App\Http\Requests\PlayerRequests\StorePlayerRequest;
use App\Http\Requests\PlayerRequests\UpdatePlayerRequest;
use App\Http\Requests\PlayerRequests\ShowPlayerRequest;
use App\Http\Requests\PlayerRequests\DestroyPlayerRequest;

class PlayerController extends Controller
{
    public function index(Request $request, string $intrams_id, string $event_id)
    {
        \Log::info('Incoming data: ', $request->all());
        $perPage = 12;
        
        $approved = $request->query('approved');
        $search = $request->query('search');
        $team_id = $request->query('activeTab');
        
        // Get the current event
        $event = Event::findOrFail($event_id);
        
        // Initialize the query based on event hierarchy
        if ($event->parent_id) {
            // This is a subevent, find all sibling subevents (including this one)
            $parentId = $event->parent_id;
            
            // Get all event IDs that share the same parent
            $siblingEventIds = Event::where('parent_id', $parentId)
                ->pluck('id')
                ->toArray();
            
            \Log::info('Subevent detected, fetching players from all subevents under parent: ' . $parentId);
            \Log::info('Sibling event IDs: ', $siblingEventIds);
            
            // Query players from all sibling events
            $query = Player::whereIn('event_id', $siblingEventIds)
                ->where('intrams_id', $intrams_id);
        } 
        else if (Event::where('parent_id', $event_id)->exists()) {
            // This is a parent event, get players from all its subevents
            $childEventIds = Event::where('parent_id', $event_id)
                ->pluck('id')
                ->toArray();
                
            \Log::info('Parent event detected, fetching players from all subevents: ', $childEventIds);
            
            // Query players from all child events
            $query = Player::whereIn('event_id', $childEventIds)
                ->where('intrams_id', $intrams_id);
        }
        else {
            // Regular event (not parent or child), get players directly assigned to it
            $query = Player::where('event_id', $event_id)
                ->where('intrams_id', $intrams_id);
        }

        // Apply filters
        if ($team_id && $team_id !== 'All') {
            $query->where('team_id', $team_id);
        }

        if ($approved && $approved !== 'All') {
            $query->where('approved', $approved);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $players = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $playersData = $players->items();
        
        // Process player data
        foreach ($playersData as $player) {
            if ($player->medical_certificate) {
                $player->medical_certificate = asset('storage/' . $player->medical_certificate);            
            }
            if ($player->cor) {
                $player->cor = asset('storage/' . $player->cor);            
            }
            if ($player->parents_consent) {
                $player->parents_consent = asset('storage/' . $player->parents_consent);            
            }
            if ($player->picture) {
                $player->picture = asset('storage/' . $player->picture);
            }
        }

        return response()->json([
            'data' => $playersData,
            'meta' => [
                'current_page' => $players->currentPage(),
                'per_page' => $players->perPage(),
                'total' => $players->total(),
                'last_page' => $players->lastPage(),
            ]
        ], 200);
    }

    public function store(StorePlayerRequest $request)
    {
        \Log::info('Incoming data:', $request->all());

        $validated = $request->validated();

        if ($request->hasFile('medical_certificate')) {
            $path = $request->file('medical_certificate')->store('player_medical_certificates', 'public');
            $validated['medical_certificate'] = $path;
        }

        if ($request->hasFile('cor')) {
            $path = $request->file('cor')->store('player_cors', 'public');
            $validated['cor'] = $path;
        }

        if ($request->hasFile('parents_consent')) {
            $path = $request->file('parents_consent')->store('player_parents_consents', 'public');
            $validated['parents_consent'] = $path;
        }

        if ($request->hasFile('picture')) {
            $path = $request->file('picture')->store('player_pictures', 'public');
            $validated['picture'] = $path;
        }

        $validated['is_varsity'] = false;
        $validated['approved'] = false;

        $event = Event::find($validated['event_id']);
        if ($event) {
            $eventType = $event->category;
            $eventName = $event->name;
            $validated['sport'] = $eventType . ' ' . $eventName;
        } else {
            $validated['sport'] = null;
        }

        $player = Player::create($validated);

        return response()->json($player, 201);
    }

    public function update(UpdatePlayerRequest $request)
    {
        \Log::info('Incoming data:', $request->all());

        $validated = $request->validated();

        \Log::info('Validated data:', $validated);

        $player = Player::where('id', $validated['id'])
                        ->firstOrFail();

        $remove_med_cert = $request->input('remove_medical_certificate', false);
        $remove_cor = $request->input('remove_cor', false);
        $remove_parents_consent = $request->input('remove_parents_consent', false);
        $remove_picture = $request->input('remove_picture', false);

        if ($request->has('id_number')) {
            if ($player->id_number !== $validated['id_number']) {
                unset($validated['id_number']);
            }
        }

        // medical_certificate
        if ($request->hasFile('medical_certificate')) {
            if ($player->medical_certificate && Storage::disk('public')->exists($player->medical_certificate)) {
                Storage::disk('public')->delete($player->medical_certificate);
            }
            $path = $request->file('medical_certificate')->store('player_medical_certificates', 'public');
            $validated['medical_certificate'] = $path;
        } elseif ($remove_med_cert) {
            if ($player->medical_certificate && Storage::disk('public')->exists($player->medical_certificate)) {
                Storage::disk('public')->delete($player->medical_certificate);
            }
            $validated['medical_certificate'] = null;
        }

        // cor
        if ($request->hasFile('cor')) {
            if ($player->cor && Storage::disk('public')->exists($player->cor)) {
                Storage::disk('public')->delete($player->cor);
            }
            $path = $request->file('cor')->store('player_cors', 'public');
            $validated['cor'] = $path;
        } elseif ($remove_cor) {
            if ($player->cor && Storage::disk('public')->exists($player->cor)) {
                Storage::disk('public')->delete($player->cor);
            }
            $validated['cor'] = null;
        }

        // parents_consent
        if ($request->hasFile('parents_consent')) {
            if ($player->parents_consent && Storage::disk('public')->exists($player->parents_consent)) {
                Storage::disk('public')->delete($player->parents_consent);
            }
            $path = $request->file('parents_consent')->store('player_parents_consents', 'public');
            $validated['parents_consent'] = $path;
        } elseif ($remove_parents_consent) {
            if ($player->parents_consent && Storage::disk('public')->exists($player->parents_consent)) {
                Storage::disk('public')->delete($player->parents_consent);
            }
            $validated['parents_consent'] = null;
        }

        // picture
        if ($request->hasFile('picture')) {
            if ($player->picture && Storage::disk('public')->exists($player->picture)) {
                Storage::disk('public')->delete($player->picture);
            }
            $path = $request->file('picture')->store('player_pictures', 'public');
            $validated['picture'] = $path;
        } elseif ($remove_picture) {
            if ($player->picture && Storage::disk('public')->exists($player->picture)) {
                Storage::disk('public')->delete($player->picture);
            }
            $validated['picture'] = null;
        }

        $player->update($validated);

        return response()->json([
            'message' => 'Player updated successfully',
            'player' => $player
        ], 200);
    }

    public function destroy(DestroyPlayerRequest $request)
    {
        \Log::info('Incoming data:', $request->all());

        $validated = $request->validated();
        $player = Player::where('id', $validated['id'])->firstOrFail();

        $files = [
            $player->medical_certificate,
            $player->cor,
            $player->parents_consent,
            $player->picture
        ];

        foreach ($files as $file) {
            if ($file && Storage::disk('public')->exists($file)) {
                Storage::disk('public')->delete($file);
            }
        }

        $player->delete();

        return response()->json(['message' => 'Player deleted successfully'], 200);
    }
}