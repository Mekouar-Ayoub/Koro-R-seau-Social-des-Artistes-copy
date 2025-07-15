<?php
// app/Models/PlaylistTrack.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaylistTrack extends Model
{
    use HasFactory;

    protected $fillable = [
        'playlist_id',
        'track_id',
        'position',
        'added_by',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    // Relations
    public function playlist()
    {
        return $this->belongsTo(Playlist::class);
    }

    public function track()
    {
        return $this->belongsTo(MusicTrack::class, 'track_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    // Scopes
    public function scopeOrderByPosition($query)
    {
        return $query->orderBy('position');
    }

    public function scopeByPlaylist($query, $playlistId)
    {
        return $query->where('playlist_id', $playlistId);
    }
}