<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'intrams_id', 
        'status',
        'challonge_event_id', 
        'tournament_type', 
        'hold_third_place_match',
        'category', 
        'type',
        'gold', 
        'silver', 
        'bronze',
        'is_umbrella',      // Flag to indicate if this is an umbrella event
        'parent_id',        // ID of the parent event (if this is a sub-event)
        'venue',             // Already in your form but missing from model
        'has_independent_medaling'  // Determines if sub-events have their own medaling system

    ];

    protected $casts = [
        'gold' => 'integer',
        'silver' => 'integer',
        'bronze' => 'integer',
        'hold_third_place_match' => 'boolean',
        'is_umbrella' => 'boolean'
    ];

    public function intramural_game() 
    {
        return $this->belongsTo(IntramuralGame::class, 'intrams_id');
    }

    public function participating_teams() 
    {
        return $this->hasMany(ParticipatingTeam::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function galleries()
    {
        return $this->hasMany(Gallery::class);
    }

    // Add relationship methods for parent-child relationships
    public function parent()
    {
        return $this->belongsTo(Event::class, 'parent_id');
    }

    public function subEvents()
    {
        return $this->hasMany(Event::class, 'parent_id');
    }

    public function calculateDependentMedals()
    {
        if (!$this->is_umbrella || $this->has_independent_medaling) {
            return null; // Not applicable for standalone or independent medaling events
        }
        
        // Get all sub-events
        $subEvents = $this->subEvents;
        
        // Initialize team scores
        $teamScores = [];
        
        // Calculate points for each team based on medal positions
        foreach ($subEvents as $subEvent) {
            // Get the podium for this sub-event
            $podium = Podium::where('event_id', $subEvent->id)->first();
            
            if ($podium) {
                // Assign points: Gold (3 points), Silver (2 points), Bronze (1 point)
                if ($podium->gold_team_id) {
                    $teamScores[$podium->gold_team_id] = ($teamScores[$podium->gold_team_id] ?? 0) + 3;
                }
                
                if ($podium->silver_team_id) {
                    $teamScores[$podium->silver_team_id] = ($teamScores[$podium->silver_team_id] ?? 0) + 2;
                }
                
                if ($podium->bronze_team_id) {
                    $teamScores[$podium->bronze_team_id] = ($teamScores[$podium->bronze_team_id] ?? 0) + 1;
                }
            }
        }
        
        // Sort teams by score in descending order
        arsort($teamScores);
        
        // Get top 3 teams
        $topTeams = array_keys(array_slice($teamScores, 0, 3, true));
        
        // Return medal winners
        return [
            'gold_team_id' => $topTeams[0] ?? null,
            'silver_team_id' => $topTeams[1] ?? null, 
            'bronze_team_id' => $topTeams[2] ?? null
        ];
    }
}