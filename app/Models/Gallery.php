<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'team_id', 'file_path', 'cloudinary_public_id'];

    public function event() 
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function team()
    {
        return $this->belongsTo(OverallTeam::class, 'team_id');
    }
}