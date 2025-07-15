<?php
// app/Models/Comment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'commentable_type',
        'commentable_id',
        'content',
        'parent_id',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    // Méthodes utiles
    public function getLikesCountAttribute()
    {
        return $this->likes()->count();
    }

    public function getRepliesCountAttribute()
    {
        return $this->replies()->count();
    }

    public function isReply()
    {
        return !is_null($this->parent_id);
    }

    public function getDepth()
    {
        $depth = 0;
        $comment = $this;
        
        while ($comment->parent_id) {
            $depth++;
            $comment = $comment->parent;
        }
        
        return $depth;
    }

    // Événements du modèle
    protected static function boot()
    {
        parent::boot();

        static::created(function ($comment) {
            // Créer une notification pour le commentaire (sauf si c'est son propre contenu)
            $commentable = $comment->commentable;
            
            if ($commentable && isset($commentable->user_id) && $commentable->user_id !== $comment->user_id) {
                Notification::create([
                    'user_id' => $commentable->user_id,
                    'type' => 'comment',
                    'notifiable_type' => get_class($commentable),
                    'notifiable_id' => $commentable->id,
                    'data' => [
                        'commenter_name' => $comment->user->name,
                        'commenter_avatar' => $comment->user->avatar,
                        'comment_content' => substr($comment->content, 0, 100),
                        'content_type' => class_basename($commentable),
                        'content_title' => $commentable->title ?? $commentable->name ?? 'contenu',
                    ]
                ]);
            }

            // Si c'est une réponse, notifier l'auteur du commentaire parent
            if ($comment->parent_id && $comment->parent->user_id !== $comment->user_id) {
                Notification::create([
                    'user_id' => $comment->parent->user_id,
                    'type' => 'reply',
                    'notifiable_type' => Comment::class,
                    'notifiable_id' => $comment->id,
                    'data' => [
                        'replier_name' => $comment->user->name,
                        'replier_avatar' => $comment->user->avatar,
                        'reply_content' => substr($comment->content, 0, 100),
                        'original_comment' => substr($comment->parent->content, 0, 50),
                    ]
                ]);
            }
        });
    }

    // Scopes
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForContent($query, $type, $id)
    {
        return $query->where('commentable_type', $type)->where('commentable_id', $id);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeWithUser($query)
    {
        return $query->with('user:id,name,avatar');
    }

    public function scopeWithReplies($query)
    {
        return $query->with(['replies' => function($q) {
            $q->withUser()->orderBy('created_at', 'asc');
        }]);
    }
}