<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntramuralGame extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'year'];

    public function venues() {
        return $this->hasMany(Venue::class);
    }

    public function teams() {
        return $this->hasMany(OverallTeam::class);
    }

    public function varsity_players() {
        return $this->hasMany(VarsityPlayer::class);
    }

    public function events() {
        return $this->hasMany(Event::class);
    }
}
