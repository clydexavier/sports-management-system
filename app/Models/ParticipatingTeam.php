<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipatingTeam extends Model
{
    use HasFactory;

    public $fillable = [
        'team_id',
        'event_id',
        'GAM_id',
    ];

    public $casts = [];

    public function event() 
    {
        return $this->belongsTo(Event::class);
    }

    public function overall_team() 
    {
        return $this->belongsTo(OverallTeam::class);
    }
}
