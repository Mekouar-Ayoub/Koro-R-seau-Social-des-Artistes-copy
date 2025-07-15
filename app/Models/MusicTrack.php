<?php
// app/Models/MusicTrack.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MusicTrack extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'title',
        'artist',
        'album',
        'genre',
        'duration',
        'file_path',
        'cloud_url',
        'file_size',
        'cover_image',
        'description',
        'is_public',
        'play_count',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'duration' => 'integer',
        'file_size' => 'integer',
        'play_count' => 'integer',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function musicPosts()
    {
        return $this->hasMany(MusicPost::class, 'track_id');
    }

    public function playlistTracks()
    {
        return $this->hasMany(PlaylistTrack::class, 'track_id');
    }

    public function playlists()
    {
        return $this->belongsToMany(Playlist::class, 'playlist_tracks', 'track_id', 'playlist_id')
            ->withPivot('position', 'added_by')
            ->withTimestamps();
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function listeningHistory()
    {
        return $this->hasMany(ListeningHistory::class, 'track_id');
    }

    // Méthodes utiles
    public function getLikesCountAttribute()
    {
        return $this->likes()->count();
    }

    public function getCommentsCountAttribute()
    {
        return $this->comments()->count();
    }

    public function getDurationFormattedAttribute()
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function incrementPlayCount()
    {
        $this->increment('play_count');
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByGenre($query, $genre)
    {
        return $query->where('genre', $genre);
    }

    public function scopePopular($query)
    {
        return $query->orderBy('play_count', 'desc');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('artist', 'like', "%{$term}%")
              ->orWhere('album', 'like', "%{$term}%");
        });
    }

    // Collections médias pour Spatie MediaLibrary
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('audio')->singleFile();
        $this->addMediaCollection('cover')->singleFile();
    }
}