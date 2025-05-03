<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntramuralGame extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location', 'status', 'start_date', 'end_date'];

    public function venues() 
    {
        return $this->hasMany(Venue::class);
    }

    public function teams() 
    {
        return $this->hasMany(OverallTeam::class, 'intrams_id');
    }

    public function events() 
    {
        return $this->hasMany(Event::class, 'intrams_id');
    }

    public function podiums()
    {
        return $this->hasMany(Podium::class, 'intrams_id');
    }
    public function players() 
    {
        return $this->hasMany(Player::class);
    }

    public function documents() 
    {
        return $this->hasMany(Document::class);
    }

}