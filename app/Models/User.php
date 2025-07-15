<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'bio',
        'location',
        'is_private',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_private' => 'boolean',
    ];

    // Relations
    public function oauthProviders()
    {
        return $this->hasMany(OauthProvider::class);
    }

    public function musicTracks()
    {
        return $this->hasMany(MusicTrack::class);
    }

    public function musicPosts()
    {
        return $this->hasMany(MusicPost::class);
    }

    public function playlists()
    {
        return $this->hasMany(Playlist::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function preferences()
    {
        return $this->hasOne(UserPreference::class);
    }

    public function listeningHistory()
    {
        return $this->hasMany(ListeningHistory::class);
    }

    // Relations de suivi
    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    // Méthodes utiles
    public function isFollowing($userId)
    {
        return $this->following()->where('following_id', $userId)->exists();
    }

    public function isFollowedBy($userId)
    {
        return $this->followers()->where('follower_id', $userId)->exists();
    }

    public function hasLiked($likeable)
    {
        return $this->likes()
            ->where('likeable_type', get_class($likeable))
            ->where('likeable_id', $likeable->id)
            ->exists();
    }

    public function getFollowersCountAttribute()
    {
        return $this->followers()->count();
    }

    public function getFollowingCountAttribute()
    {
        return $this->following()->count();
    }

    public function getTracksCountAttribute()
    {
        return $this->musicTracks()->count();
    }

    // Scope pour les utilisateurs publics
    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    // Collections médias pour Spatie MediaLibrary
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }
}