<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OverallTeam;
use App\Models\IntramuralGame;
use App\Http\Requests\OverallTeamRequests\StoreOverallTeamRequest;
use App\Http\Requests\OverallTeamRequests\UpdateOverallTeamRequest;
use App\Http\Requests\OverallTeamRequests\UpdateOverallTeamMedalRequest;


class OverallTeamController extends Controller
{
    //
    public function index(string $intrams_id) 
    {
        $overall_teams = OverallTeam::where('intrams_id', $intrams_id)->get();
        return response()->json($overall_teams, 200);
    }

    public function store(StoreOverallTeamRequest $request) 
    {
        $validated =  $request->validated();
    
        $overall_team = OverallTeam::create($validated);
    
        return response()->json($overall_team, 201);
    }

    public function show(string $intrams_id, string $id) 
    {
        $overall_team = OverallTeam::where('id', $id) -> where('intrams_id', $intrams_id)->firstOrFail();
        
        return response()->json($overall_team, 200);
    }

    public function update_info(UpdateOverallTeamRequest $request) 
    {
        $validated = $request->validated();

        $overall_team = OverallTeam::where('id', $validated['id'])->firstOrFail();

        $overall_team->update($validated);

        return response()->json([
            'message' => 'Team info updated successfully',
            'team' => $overall_team
        ], 200);
    }

    public function update_medal(UpdateOverallTeamMedalRequest $request) 
    {
        $validated = $request->validated();
        $team = OverallTeam::where('id', $validated['id'])
                 ->firstOrFail();
                 
        $team->increment('total_gold', $validated['total_gold']);
        $team->increment('total_silver', $validated['total_silver']);
        $team->increment('total_bronze', $validated['total_bronze']);

        return response()->json(['message' => 'Medals updated successfully', 'team' => $team], 200);
    }

    public function destroy(string $intrams_id,string $id) 
    {
        //
        $overall_team = OverallTeam::where('id', $id)
                    ->where('intrams_id', $intrams_id)
                    ->firstOrFail();
                    
        $overall_team->delete();
        return response()->json(['message' => 'Team deleted successfully.'], 204);       
    }
}
