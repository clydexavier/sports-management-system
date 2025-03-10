<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'intrams_id', 'challonge_event_id', 'tournament_type', 'hold_third_place_match','category', 'type','gold', 'silver', 'bronze'];

    protected $casts = [
        'gold' => 'integer',
        'silver' => 'integer',
        'bronze' => 'integer'
    ];

    public function intramural_game() {
        return $this->belongsTo(IntramuralGame::class, 'intrams_id');
    }

}
