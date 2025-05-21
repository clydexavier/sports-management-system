<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OverallTeam;
use App\Models\ParticipatingTeam;
use App\Models\IntramuralGame;
use App\Http\Requests\OverallTeamRequests\StoreOverallTeamRequest;
use App\Http\Requests\OverallTeamRequests\UpdateOverallTeamRequest;
use App\Http\Requests\OverallTeamRequests\UpdateOverallTeamMedalRequest;
use Cloudinary\Cloudinary;

class OverallTeamController extends Controller
{
    protected $cloudinary;

    public function __construct()
    {
        // Initialize Cloudinary with the CLOUDINARY_URL from .env
        $this->cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
    }

    public function index(Request $request, string $intrams_id)
    {
        \Log::info('Incoming data: ', $request->all());
        $perPage = 12;
        
        $type = $request->query('type');
        $search = $request->query('search');
        
        $query = OverallTeam::where('intrams_id', $intrams_id);
        
        if ($type && $type !== 'All') {
            $query->where('type', $type);
        }
        
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        
        $teams = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return response()->json([
            'data' => $teams->items(),
            'meta' => [
                'current_page' => $teams->currentPage(),
                'per_page' => $teams->perPage(),
                'total' => $teams->total(),
                'last_page' => $teams->lastPage(),
            ]
        ], 200);
    }

    public function index_team_name(Request $request, string $intrams_id) 
    {
        $teams = OverallTeam::where('intrams_id', $intrams_id)
                    ->orderBy('created_at', 'desc')
                    ->get(['id', 'name']);
        return response()->json($teams, 200);
    }

    public function store(StoreOverallTeamRequest $request) 
    {
        $validated = $request->validated();

        if ($request->hasFile('team_logo_path')) {
            // Upload file to Cloudinary
            $uploadedFile = $request->file('team_logo_path');
            $result = $this->cloudinary->uploadApi()->upload(
                $uploadedFile->getRealPath(),
                ['folder' => 'team_logos']
            );

            // Store the Cloudinary URL
            $validated['team_logo_path'] = $result['secure_url'];
            
            // Store the public_id for deletion later
            $validated['team_logo_public_id'] = $result['public_id'];
        }

        $overall_team = OverallTeam::create($validated);
        return response()->json($overall_team, 201);
    }

    public function show(string $intrams_id, string $id) 
    {
        $overall_team = OverallTeam::where('id', $id)->where('intrams_id', $intrams_id)->firstOrFail();
        return response()->json($overall_team, 200);
    }

    public function update_info(UpdateOverallTeamRequest $request) 
    {
        \Log::info('Incoming data:', $request->all());

        $validated = $request->validated();
        \Log::info('Validated data:', $validated);
        
        $overall_team = OverallTeam::where('id', $validated['id'])
                    ->where('intrams_id', $validated['intrams_id'])
                    ->firstOrFail();
        
        // Handle logo removal if requested (using direct request input instead of validated data)
        if ($request->input('remove_logo') == '1' && $overall_team->team_logo_path) {
            // Delete from Cloudinary if public_id exists
            if ($overall_team->team_logo_public_id) {
                $this->cloudinary->uploadApi()->destroy($overall_team->team_logo_public_id);
            }
            
            // Set both fields to null regardless
            $validated['team_logo_path'] = null;
            $validated['team_logo_public_id'] = null;
            
            \Log::info('Removing logo for team: ' . $overall_team->id);
        }
        // Handle new logo upload
        elseif ($request->hasFile('team_logo_path')) {
            // Delete old image if exists
            if ($overall_team->team_logo_public_id) {
                $this->cloudinary->uploadApi()->destroy($overall_team->team_logo_public_id);
            }
            
            // Upload new image
            $uploadedFile = $request->file('team_logo_path');
            $result = $this->cloudinary->uploadApi()->upload(
                $uploadedFile->getRealPath(),
                ['folder' => 'team_logos']
            );

            // Store the Cloudinary URL and public_id
            $validated['team_logo_path'] = $result['secure_url'];
            $validated['team_logo_public_id'] = $result['public_id'];
        }

        // Update the model with all validated data at once
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

    public function destroy(string $intrams_id, string $id) 
    {
        $overall_team = OverallTeam::where('id', $id)
                    ->where('intrams_id', $intrams_id)
                    ->firstOrFail();

        // Delete from Cloudinary if exists
        if ($overall_team->team_logo_public_id) {
            $this->cloudinary->uploadApi()->destroy($overall_team->team_logo_public_id);
        }
        
        $overall_team->delete();
        return response()->json(['message' => 'Team deleted successfully.'], 204);       
    }
}