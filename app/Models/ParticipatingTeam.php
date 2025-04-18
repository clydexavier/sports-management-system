<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipatingTeam extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'team_id',
        'event_id',
        'finalized',
        'GAM_id',
    ];

    public $casts = [];

    public function event() 
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function overall_team() 
    {
        return $this->belongsTo(OverallTeam::class, 'team_id');
    }
}
