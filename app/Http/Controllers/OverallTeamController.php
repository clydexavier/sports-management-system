<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OverallTeam;
use App\Models\IntramuralGame;

class OverallTeamController extends Controller
{
    //
    public function index(string $intrams_id) 
    {
        $overall_teams = OverallTeam::where('intrams_id', $intrams_id)->get();
        return response()->json($overall_teams, 200);
    }

    public function store(Request $request, string $intrams_id ) 
    {
       $validated =  $request->validate([
            'name' => ['required','string', 'max:50'],
            'team_logo_path' => ['sometimes', 'string', 'max:255'],
            'total_gold' => ['integer', 'min:0'],
            'total_silver' => ['integer', 'min:0'],
            'total_bronze' => ['integer', 'min:0'],
        ]);

        $intrams = IntramuralGame::findOrFail($intrams_id);

        $overall_team = OverallTeam::create([
            'name' => $validated['name'],
            'team_logo_path' => $validated['team_logo_path'] ?? 'images/default/xd.png',
            'total_gold' => 0,
            'total_silver' => 0,
            'total_bronze' => 0,
            'intrams_id' => $intrams_id,
        ]);

        return response()->json($overall_team, 201);
    }

    public function show(string $intrams_id, string $id) 
    {
        $overall_team = OverallTeam::where('id', $id) -> where('intrams_id', $intrams_id)->firstOrFail();
        
        return response()->json($overall_team, 200);
    }

    public function update_info(Request $request, string $intrams_id, string $id) 
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'], 
            'team_logo_path' => ['sometimes', 'string', 'max:255'],
        ]);

        $overall_team = OverallTeam::where('id', $id)
                 ->where('intrams_id', $intrams_id)
                 ->firstOrFail();

        $overall_team->update($validated);
        return response()->json(['message' => 'Team info updated successfully', 'team' => $overall_team], 200);
    }

    public function update_medal(Request $request, string $intrams_id, string $id) 
    {
        $validated = $request->validate([
            'total_gold' => ['sometimes', 'integer', 'min:0'],
            'total_silver' => ['sometimes', 'integer', 'min:0'],
            'total_bronze' => ['sometimes' , 'integer', 'min:0'],
        ]);

        $overall_team = OverallTeam::where('id', $id)
                 ->where('intrams_id', $intrams_id)
                 ->firstOrFail();

        //mali ni nga logic (foreach)
        //dapat i check sa if valid tanan usa i dungan og update tanan to
        //avoid nga if 1 ra ang valid ma update na nuon pero ang uban medals kay dili
        foreach (['total_gold', 'total_silver', 'total_bronze'] as $medal) {
            if (isset($validated[$medal])) {
                $overall_team->increment($medal, $validated[$medal]);
            }
        }
        return response()->json(['message' => 'Medals updated successfully', 'team' => $overall_team], 200);
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
