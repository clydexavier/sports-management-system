<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'file_path',
        'mime_type',
        'description',
        'size',
        'intrams_id',
    ];

    /**
     * Relationship: A document belongs to an intramural game.
     */
    public function intramuralGame()
    {
        return $this->belongsTo(IntramuralGame::class, 'intrams_id');
    }

    /**
     * Get the full file URL using Laravel's storage.
     */
    public function getFileUrlAttribute()
    {
        return Storage::url($this->file_path);
    }
}
