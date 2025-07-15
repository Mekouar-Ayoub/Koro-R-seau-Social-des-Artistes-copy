<?php
// app/Models/Like.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'likeable_type',
        'likeable_id',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likeable()
    {
        return $this->morphTo();
    }

    // Événements du modèle
    protected static function boot()
    {
        parent::boot();

        static::created(function ($like) {
            // Créer une notification pour le like (sauf si c'est son propre contenu)
            $likeable = $like->likeable;
            
            if ($likeable && isset($likeable->user_id) && $likeable->user_id !== $like->user_id) {
                Notification::create([
                    'user_id' => $likeable->user_id,
                    'type' => 'like',
                    'notifiable_type' => get_class($likeable),
                    'notifiable_id' => $likeable->id,
                    'data' => [
                        'liker_name' => $like->user->name,
                        'liker_avatar' => $like->user->avatar,
                        'content_type' => class_basename($likeable),
                        'content_title' => $likeable->title ?? $likeable->name ?? 'contenu',
                    ]
                ]);
            }
        });
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForContent($query, $type, $id)
    {
        return $query->where('likeable_type', $type)->where('likeable_id', $id);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}