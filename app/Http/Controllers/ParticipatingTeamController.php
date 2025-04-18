<?php

namespace App\Http\Controllers;

use App\Models\ParticipatingTeam;


use Illuminate\Http\Request;
use App\Http\Requests\ParticipatingTeamRequests\StorePTRequest;
use App\Http\Requests\ParticipatingTeamRequests\ShowPTRequest;
use App\Http\Requests\ParticipatingTeamRequests\UpdatePTRequest;
use App\Http\Requests\ParticipatingTeamRequests\DeletePTRequest;

class ParticipatingTeamController extends Controller
{
    //
    public function index(Request $request, string $intrams_id, string $event_id)
    {
        \Log::info('Incoming data: ', $request->all());

        $perPage = 12;
        $finalized = $request->query('finalized');
        $search = $request->query('search');

        $query = ParticipatingTeam::with('overall_team')->where('event_id', $event_id);
        
        if ($finalized && $finalized !== 'All') {
            $query->where('finalized', $finalized);
        }
        
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        
        $participating_teams = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return response()->json([
            'data' => $participating_teams->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'finalized' => $team->finalized,
                    'team_id' => $team->team_id,
                    'event_id' => $team->event_id,
                    'team_name' => optional($team->overall_team)->name,
                    'team_logo' => optional($team->overall_team)->team_logo_path,
                ];
            }),
            'meta' => [
                'current_page' => $participating_teams->currentPage(),
                'per_page' => $participating_teams->perPage(),
                'total' => $participating_teams->total(),
                'last_page' => $participating_teams->lastPage(),
            ]
        ], 200);
        
        
    }

    public function store(StorePTRequest $request)
    {
        $validated = $request->validated();

        $participating_team = ParticipatingTeam::create($validated);
        return response()->json($participating_team, 201);

    }

    public function show(ShowPTRequest $request)
    {
        $validated = $request->validated();
        $participating_team = ParticipatingTeam::where('id', $validated['id'])->where('event_id', $validated['event_id'])->firstOrFail();
        return response()->json([
            'data' => [
                'id' => $participating_team->id,
                'name' => $participating_team->name,
                'finalized' => $participating_team->finalized,
                'team_id' => $participating_team->team_id,
                'team_name' => optional($participating_team->overall_team)->name,
                'team_logo' => optional($participating_team->overall_team)->team_logo_path,
            ]
        ], 200);
    }

    public function update(UpdatePTRequest $request)
    {
        $validated = $request->validated();
        $participating_team = ParticipatingTeam::where('id', $validated['id'])->where('event_id', $validated['event_id'])->firstOrFail();
        $participating_team->update($validated);
        return response()->json([
            'message' => 'Participating Team updated successfully',
            'participating_team' => $participating_team
        ], 200);

    }

    public function destroy(DeletePTRequest $request)
    {
        $validated = $request->validated();
        $participating_team = ParticipatingTeam::where('id', $validated['id'])->where('event_id', $validated['event_id'])->firstOrFail();
        $participating_team->delete();
        return response()->json([
            'message' => 'Participating Team deleted successfully',
            'participating_team' => $participating_team
        ], 200);
    }
}
