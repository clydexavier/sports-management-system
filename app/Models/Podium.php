<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Podium extends Model
{
    use HasFactory;

    protected $table = 'podiums';

    protected $fillable = [
        'intrams_id',
        'event_id',
        'gold_team_id',
        'silver_team_id',
        'bronze_team_id'
    ];

    protected $casts = [
        'gold_team_id' => 'integer',
        'silver_team_id' => 'integer',
        'bronze_team_id' => 'integer',
    ];

    public function intramural() 
    {
        return $this->belongsTo(intramural::class, 'intrams_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
    
    public function gold()
    {
        return $this->belongsTo(OverallTeam::class, 'gold_team_id');
    }

    public function silver()
    {
        return $this->belongsTo(OverallTeam::class, 'silver_team_id');
    }
    
    public function bronze()
    {
        return $this->belongsTo(OverallTeam::class, 'bronze_team_id');
    }

    public function tsecretary()
    {
        return $this->has(User::class);
    }
}