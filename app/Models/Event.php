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
}