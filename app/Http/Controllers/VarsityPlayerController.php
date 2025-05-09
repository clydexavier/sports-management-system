<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PlayerRequests\StoreVarsityPlayerRequest;
use App\Http\Requests\PlayerRequests\UpdateVarsityPlayerRequest;
use App\Http\Requests\PlayerRequests\DestroyVarsityPlayerRequest;
use App\Http\Requests\PlayerRequests\TeamsVarsityPlayerRequest;
use App\Models\Player;

class VarsityPlayerController extends Controller
{
    public function index(string $intrams_id, Request $request) 
    {
        $perPage = 5;

        $search = $request->query('search');

        $query = Player::where('intrams_id', $intrams_id)->where('is_varsity', true);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhere('middle_name', 'like', '%' . $search . '%');
            });
        }

        $varsity_players = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $varsity_players->items(),
            'meta' => [
                'current_page' => $varsity_players->currentPage(),
                'per_page' => $varsity_players->perPage(),
                'total' => $varsity_players->total(),
                'last_page' => $varsity_players->lastPage(),
            ]
        ], 200);
    }

    public function store(StoreVarsityPlayerRequest $request) 
    {
        $validated = $request->validated();
        $validated['is_varsity'] = true;

        $varsity_player = Player::create($validated);

        return response()->json($varsity_player, 201);
    }

    public function update(UpdateVarsityPlayerRequest $request)
    {
        $validated = $request->validated();

        $varsity_player = Player::where('id', $validated['id'])
            ->where('intrams_id', $validated['intrams_id'])
            ->where('is_varsity', true)
            ->firstOrFail();

        $varsity_player->update($validated);

        return response()->json([
            'message' => 'Varsity player updated successfully',
            'varsity_player' => $varsity_player
        ], 200);
    }

    public function vplayer_sports(TeamsVarsityPlayerRequest $request)
    {
        $validated = $request->validated();
        $sports = Player::where('is_varsity', true)->where('intrams_id', $validated['intrams_id'])
        ->whereNotNull('sport')
        ->distinct()
        ->pluck('sport');

    return response()->json([
        'data' => $sports,
    ], 200);
    }

    public function destroy(DestroyVarsityPlayerRequest $request) 
    {
        $validated = $request->validated();

        $varsity_player = Player::where('id', $validated['id'])
            ->where('intrams_id', $validated['intrams_id'])
            ->where('is_varsity', true)
            ->firstOrFail();

        $varsity_player->delete();

        return response()->json(['message' => 'Varsity player deleted successfully.'], 204);
    }
}