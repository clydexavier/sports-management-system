<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_1',
        'team_2',
        'event_id',
        'intrams_id',
        'challonge_event_id',
        'match_id',
        'date',
        'time'
    ];

    public function event()
    {
        return this->belongsTo(Event::class, 'event_id');
    }

    public function intramural()
    {
        return this->belongsTo(IntramuralGame::class, 'intrams_id');
    }
}