<?php
// app/Models/Playlist.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Playlist extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'cover_image',
        'is_public',
        'is_collaborative',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_collaborative' => 'boolean',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function playlistTracks()
    {
        return $this->hasMany(PlaylistTrack::class)->orderBy('position');
    }

    public function tracks()
    {
        return $this->belongsToMany(MusicTrack::class, 'playlist_tracks', 'playlist_id', 'track_id')
            ->withPivot('position', 'added_by')
            ->withTimestamps()
            ->orderBy('playlist_tracks.position');
    }

    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'playlist_collaborators', 'playlist_id', 'user_id')
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

    // Méthodes utiles
    public function getTotalDurationAttribute()
    {
        return $this->tracks()->sum('duration');
    }

    public function getTotalDurationFormattedAttribute()
    {
        $totalSeconds = $this->total_duration;
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getTracksCountAttribute()
    {
        return $this->tracks()->count();
    }

    public function getLikesCountAttribute()
    {
        return $this->likes()->count();
    }

    public function getCommentsCountAttribute()
    {
        return $this->comments()->count();
    }

    public function addTrack($trackId, $userId, $position = null)
    {
        if ($position === null) {
            $position = $this->playlistTracks()->max('position') + 1;
        }

        return $this->playlistTracks()->create([
            'track_id' => $trackId,
            'position' => $position,
            'added_by' => $userId,
        ]);
    }

    public function removeTrack($trackId)
    {
        $playlistTrack = $this->playlistTracks()->where('track_id', $trackId)->first();
        
        if ($playlistTrack) {
            $position = $playlistTrack->position;
            $playlistTrack->delete();
            
            // Réorganiser les positions
            $this->playlistTracks()
                ->where('position', '>', $position)
                ->decrement('position');
            
            return true;
        }
        
        return false;
    }

    public function reorderTracks($trackIds)
    {
        foreach ($trackIds as $index => $trackId) {
            $this->playlistTracks()
                ->where('track_id', $trackId)
                ->update(['position' => $index + 1]);
        }
    }

    public function canEdit($userId)
    {
        return $this->user_id === $userId || 
               ($this->is_collaborative && $this->collaborators()->where('user_id', $userId)->exists());
    }

    public function canView($userId = null)
    {
        if ($this->is_public) {
            return true;
        }

        if ($userId === null) {
            return false;
        }

        return $this->user_id === $userId || 
               $this->collaborators()->where('user_id', $userId)->exists();
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCollaborative($query)
    {
        return $query->where('is_collaborative', true);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // Collections médias pour Spatie MediaLibrary
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }
}