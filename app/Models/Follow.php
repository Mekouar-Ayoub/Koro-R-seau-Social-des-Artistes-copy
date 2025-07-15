<?php
// app/Models/Follow.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    use HasFactory;

    protected $fillable = [
        'follower_id',
        'following_id',
    ];

    // Relations
    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function following()
    {
        return $this->belongsTo(User::class, 'following_id');
    }

    // Événements du modèle
    protected static function boot()
    {
        parent::boot();

        static::created(function ($follow) {
            // Créer une notification pour le suivi
            Notification::create([
                'user_id' => $follow->following_id,
                'type' => 'follow',
                'notifiable_type' => User::class,
                'notifiable_id' => $follow->follower_id,
                'data' => [
                    'follower_name' => $follow->follower->name,
                    'follower_avatar' => $follow->follower->avatar,
                ]
            ]);
        });
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('follower_id', $userId)->orWhere('following_id', $userId);
    }

    public function scopeFollowers($query, $userId)
    {
        return $query->where('following_id', $userId);
    }

    public function scopeFollowing($query, $userId)
    {
        return $query->where('follower_id', $userId);
    }
}