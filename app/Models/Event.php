<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'intrams_id', 'category', 'type','gold', 'silver', 'bronze'];

    protected $casts = [
        'golds' => 'integer',
        'silver' => 'integer',
        'bronze' => 'integer'
    ];

    public function intramural_game() {
        return $this->belongsTo(IntramuralGame::class, 'intrams_id');
    }

}
