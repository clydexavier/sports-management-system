<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'id_number',
        'is_varsity',
        'sport',
        'team_id',
        'intrams_id',
        'medical_certificate',
        'parents_consent',
        'cor',
        'approved',
    ];


    protected $casts = [
        'name' => 'string',
        'id_number' => 'string',
        'is_varsity' => 'boolean',
        'sport' => 'string',
    ];
    
    public function intramural_game() {
        return $this->belongsTo(IntramuralGame::class, 'intrams_id');
    }

    public function overall_team() {
        return $this->belongsTo(OverallTeam::class, 'participant_id');
    }

    public function event() {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function isVarsity() {
        return $this->is_varsity;
    }

    
}
