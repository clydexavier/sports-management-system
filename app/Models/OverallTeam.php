<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OverallTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'type',
        'team_logo_path', 
        'total_gold', 
        'total_silver', 
        'total_bronze', 
        'intrams_id'
    ];

    protected $attributes = [
        'total_gold' => 0,
        'total_silver' => 0,
        'total_bronze' => 0,
    ];

    protected $casts = [
        'total_gold' => 'integer',
        'total_silver' => 'integer',
        'total_bronze' => 'integer',
        'name' => 'string',
        'team_logo_path' => 'string',
    ];

    public function intramural_game()
    {
        return $this->belongsTo(IntramuralGame::class, 'intrams_id');
    }

<<<<<<< HEAD
=======

>>>>>>> dd8e76a ([UPDATE] PlayerController now modified routing)
    public function players()
    {
        return $this->hasMany(Player::class);
    }

}