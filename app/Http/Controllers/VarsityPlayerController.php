<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IntramuralGame;
use App\Http\Requests\PlayerRequests\StoreVarsityPlayerRequest;
use App\Http\Requests\PlayerRequests\UpdateVarsityPlayerRequest;
use App\Http\Requests\PlayerRequests\ShowVarsityPlayerRequest;
use App\Http\Requests\PlayerRequests\DestroyVarsityPlayerRequest;

use App\Models\Player;


class VarsityPlayerController extends Controller
{
    //
    public function index(string $intrams_id) 
    {
        $varsity_players = Player::where('intrams_id', $intrams_id)->where('is_varsity', true)->get();
        return response()->json($varsity_players, 200);
    }

    public function store(StoreVarsityPlayerRequest $request) 
    {
        $validated = $request->validated();
        $validated['is_varsity']= true;
        $varsity_player = Player::create($validated);
        return response()->json($varsity_player, 201);
    }

    public function show(ShowVarsityPlayerRequest $request) 
    {
        $validated = $request->validated();
        $varsity_player = Player::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->where('is_varsity', true)->firstOrFail();
        return response()->json($varsity_player, 200);
    }


    public function update(UpdateVarsityPlayerRequest $request) 
    {
        $validated = $request->validated();
        $varsity_player = Player::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();
        $varsity_player->update($validated);

        return response()->json(['message' => 'Varsity updated successfully', 'varsity_player' => $varsity_player], 200);
    }

    public function destroy(DestroyVarsityPlayerRequest $request) 
    {
        $validated = $request->validated();
        $varsity_player = Player::where('id', $validated['id'])->where('intrams_id', $validated['intrams_id'])->firstOrFail();
        $varsity_player->delete();
        return response()->json(['message' => 'Varsity Player deleted successfully.'], 204);
    }
}
