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
        'birthdate',
        'course_year',
        'contact',
        'is_varsity',
        'sport',
        'intrams_id',
        'team_id',
        'event_id',
        'picture',
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
        return $this->belongsTo(OverallTeam::class, 'team_id');
    }


    public function isVarsity() {
        return $this->is_varsity;
    }

    
}