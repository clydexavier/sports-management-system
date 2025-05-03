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
        'gold',
        'silver',
        'bronze'
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
        return $this->has(OverallTeam::class);
    }

    public function silver()
    {
        return $this->has(OverallTeam::class);
    }
    
    public function bronze()
    {
        return $this->has(OverallTeam::class);
    }
}