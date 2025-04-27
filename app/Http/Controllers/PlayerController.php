<?php

namespace App\Http\Controllers;
use App\Models\Player;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use App\Models\IntramuralGame;
use App\Http\Requests\PlayerRequests\StorePlayerRequest;
use App\Http\Requests\PlayerRequests\UpdatePlayerRequest;
use App\Http\Requests\PlayerRequests\ShowPlayerRequest;
use App\Http\Requests\PlayerRequests\DestroyPlayerRequest;


class PlayerController extends Controller
{
    //
    public function index(Request $request, string $intrams_id, string $event_id)
    {
        \Log::info('Incoming data: ', $request->all());
        $perPage = 12;
        
        $approved = $request->query('approved');
        $search = $request->query('search');
        
        $query = Player::where('event_id', $event_id);
        
        if ($approved && $approved !== 'All') {
            $query->where('approved', $approved);
        }
        
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        /*
        $teams = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        // Transform the data to include the full URL for team logos
        $teamsData = $teams->items();
        foreach ($teamsData as $team) {
            if ($team->team_logo_path) {
                $team->team_logo_path = asset('storage/' . $team->team_logo_path);            
            }
        }
        */


        $players = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $playersData = $players->items();
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

        $validated['is_varsity'] = false;
        $validated['approved'] = false;

        $participatingTeam = \App\Models\ParticipatingTeam::with('event')->find($validated['participant_id']);

        if ($participatingTeam && $participatingTeam->event) {
            $eventType = $participatingTeam->event->category; // e.g., "Men" or "Women"
            $eventName = $participatingTeam->event->name;     // e.g., "Basketball"
            $validated['sport'] = strtolower($eventType . ' ' . $eventName); // e.g., "men basketball"
        } else {
            $validated['sport'] = null; // or handle fallback
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
                        ->where('participant_id', $validated['participant_id'])
                        ->firstOrFail();

        // Handle file removals
        $remove_med_cert = $request->input('remove_medical_certificate', false);
        $remove_cor = $request->input('remove_cor', false);
        $remove_parents_consent = $request->input('remove_parents_consent', false);

        if ($request->has('id_number')) {
            if ($player->id_number !== $validated['id_number']) 
                // Remove it to avoid unnecessary update
                unset($validated['id_number']);
            
        }

        // Handle medical certificate
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

        // Handle COR
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

        // Handle Parents Consent
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

        // Update the player with validated data
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
        $player = Player::where('id', $validated['id'])
                        ->where('participant_id', $validated['participant_id'])
                        ->firstOrFail();

        if ($player->medical_certificate && Storage::disk('public')->exists($player->medical_certificate)) {
            Storage::disk('public')->delete($player->medical_certificate);
        }

        if ($player->cor && Storage::disk('public')->exists($player->cor)) {
            Storage::disk('public')->delete($player->cor);
        }

        if ($player->parents_consent && Storage::disk('public')->exists($player->parents_consent)) {
            Storage::disk('public')->delete($player->parents_consent);
        }

        $player->delete();

        return response()->json(['message' => 'Player deleted successfully'], 200);
    }
}
