<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'venue_id', 'category', 'golds', 'silver', 'bronze'];

    protected $casts = [
        'golds' => 'integer',
        'silver' => 'integer',
        'bronze' => 'integer'
    ];

    public function venue() {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

}
