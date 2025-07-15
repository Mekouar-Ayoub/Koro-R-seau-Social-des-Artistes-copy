<?php
// app/Models/Notification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable()
    {
        return $this->morphTo();
    }

    // Méthodes utiles
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    public function markAsUnread()
    {
        $this->update(['read_at' => null]);
    }

    public function isRead()
    {
        return !is_null($this->read_at);
    }

    public function isUnread()
    {
        return is_null($this->read_at);
    }

    public function getFormattedMessageAttribute()
    {
        $data = $this->data;
        
        switch ($this->type) {
            case 'like':
                return "{$data['liker_name']} a aimé votre {$data['content_type']} \"{$data['content_title']}\"";
            
            case 'comment':
                return "{$data['commenter_name']} a commenté votre {$data['content_type']} \"{$data['content_title']}\"";
            
            case 'reply':
                return "{$data['replier_name']} a répondu à votre commentaire";
            
            case 'follow':
                return "{$data['follower_name']} vous suit maintenant";
            
            case 'new_track':
                return "{$data['artist_name']} a publié un nouveau morceau \"{$data['track_title']}\"";
            
            case 'playlist_add':
                return "{$data['user_name']} a ajouté votre morceau \"{$data['track_title']}\" à sa playlist \"{$data['playlist_name']}\"";
            
            case 'collaboration_invite':
                return "{$data['inviter_name']} vous a invité à collaborer sur la playlist \"{$data['playlist_name']}\"";
                
            default:
                return "Nouvelle notification";
        }
    }

    public function getIconAttribute()
    {
        switch ($this->type) {
            case 'like':
                return 'heart';
            case 'comment':
            case 'reply':
                return 'message-circle';
            case 'follow':
                return 'user-plus';
            case 'new_track':
                return 'music';
            case 'playlist_add':
                return 'list-plus';
            case 'collaboration_invite':
                return 'users';
            default:
                return 'bell';
        }
    }

    public function getColorAttribute()
    {
        switch ($this->type) {
            case 'like':
                return 'text-red-500';
            case 'comment':
            case 'reply':
                return 'text-blue-500';
            case 'follow':
                return 'text-green-500';
            case 'new_track':
                return 'text-purple-500';
            case 'playlist_add':
                return 'text-yellow-500';
            case 'collaboration_invite':
                return 'text-indigo-500';
            default:
                return 'text-gray-500';
        }
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Méthodes statiques pour créer des notifications
    public static function createLikeNotification($userId, $likeable, $liker)
    {
        return static::create([
            'user_id' => $userId,
            'type' => 'like',
            'notifiable_type' => get_class($likeable),
            'notifiable_id' => $likeable->id,
            'data' => [
                'liker_name' => $liker->name,
                'liker_avatar' => $liker->avatar,
                'content_type' => class_basename($likeable),
                'content_title' => $likeable->title ?? $likeable->name ?? 'contenu',
            ]
        ]);
    }

    public static function createFollowNotification($userId, $follower)
    {
        return static::create([
            'user_id' => $userId,
            'type' => 'follow',
            'notifiable_type' => User::class,
            'notifiable_id' => $follower->id,
            'data' => [
                'follower_name' => $follower->name,
                'follower_avatar' => $follower->avatar,
            ]
        ]);
    }

    public static function createNewTrackNotification($userId, $track, $artist)
    {
        return static::create([
            'user_id' => $userId,
            'type' => 'new_track',
            'notifiable_type' => MusicTrack::class,
            'notifiable_id' => $track->id,
            'data' => [
                'artist_name' => $artist->name,
                'artist_avatar' => $artist->avatar,
                'track_title' => $track->title,
                'track_cover' => $track->cover_image,
            ]
        ]);
    }
}