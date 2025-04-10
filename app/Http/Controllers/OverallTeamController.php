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
    public function index(Request $request, string $intrams_id)
    {
        $perPage = 12;
        
        $status = $request->query('status');
        $search = $request->query('search');
        
        $query = OverallTeam::where('intrams_id', $intrams_id);
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        
        $teams = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        // Transform the data to include the full URL for team logos
        $teamsData = $teams->items();
        foreach ($teamsData as $team) {
            if ($team->team_logo_path) {
                $team->team_logo_path = asset('storage/' . $team->team_logo_path);            }
        }
        
        return response()->json([
            'data' => $teamsData,
            'meta' => [
                'current_page' => $teams->currentPage(),
                'per_page' => $teams->perPage(),
                'total' => $teams->total(),
                'last_page' => $teams->lastPage(),
            ]
        ], 200);
    }

    public function store(StoreOverallTeamRequest $request) 
    {
        $validated = $request->validated();

        if ($request->hasFile('team_logo_path')) {
            // Store the file in `storage/app/public/team_logos`
            $path = $request->file('team_logo_path')->store('team_logos', 'public');

            // Update the validated data with the relative path
            $validated['team_logo_path'] = $path;
        }

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

        // Handle logo removal if requested
        if ($request->has('remove_logo') && $request->input('remove_logo') == '1') {
            // Delete the old file from storage if it exists
            if ($overall_team->team_logo_path && Storage::disk('public')->exists($overall_team->team_logo_path)) {
                Storage::disk('public')->delete($overall_team->team_logo_path);
            }
            
            // Clear the logo path in the database
            $validated['team_logo_path'] = null;
        }
        // Handle new logo upload
        else if ($request->hasFile('team_logo_path')) {
            // Delete old file if exists
            if ($overall_team->team_logo_path && Storage::disk('public')->exists($overall_team->team_logo_path)) {
                Storage::disk('public')->delete($overall_team->team_logo_path);
            }
            
            // Store the new file
            $path = $request->file('team_logo_path')->store('team_logos', 'public');
            $validated['team_logo_path'] = $path;
        }
        
        $overall_team->update($validated);
        return response()->json($overall_team, 200);
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