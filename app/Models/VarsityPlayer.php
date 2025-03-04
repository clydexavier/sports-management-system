<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VarsityPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'id_number',
        'sport',
        'intrams_id',
    ];

    public function intramural_game()
    {
        return $this->belongsTo(IntramuralGame::class, 'intrams_id');
    }
}
