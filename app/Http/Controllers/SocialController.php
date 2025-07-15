<?php
// app/Http/Controllers/SocialController.php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Comment;
use App\Models\MusicTrack;
use App\Models\MusicPost;
use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SocialController extends Controller
{
    public function like(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'likeable_type' => 'required|in:track,post,playlist,comment',
            'likeable_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $likeableType = $this->getModelClass($request->likeable_type);
        $likeable = $likeableType::findOrFail($request->likeable_id);

        $currentUser = $request->user();

        // Vérifier si déjà liké
        $existingLike = Like::where('user_id', $currentUser->id)
                           ->where('likeable_type', $likeableType)
                           ->where('likeable_id', $likeable->id)
                           ->first();

        if ($existingLike) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà aimé ce contenu'
            ], 400);
        }

        Like::create([
            'user_id' => $currentUser->id,
            'likeable_type' => $likeableType,
            'likeable_id' => $likeable->id,
        ]);

        $likesCount = $likeable->likes()->count();

        return response()->json([
            'success' => true,
            'message' => 'Like ajouté',
            'data' => [
                'is_liked' => true,
                'likes_count' => $likesCount
            ]
        ]);
    }

    public function unlike(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'likeable_type' => 'required|in:track,post,playlist,comment',
            'likeable_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $likeableType = $this->getModelClass($request->likeable_type);
        $likeable = $likeableType::findOrFail($request->likeable_id);

        $currentUser = $request->user();

        $like = Like::where('user_id', $currentUser->id)
                   ->where('likeable_type', $likeableType)
                   ->where('likeable_id', $likeable->id)
                   ->first();

        if (!$like) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas aimé ce contenu'
            ], 400);
        }

        $like->delete();

        $likesCount = $likeable->likes()->count();

        return response()->json([
            'success' => true,
            'message' => 'Like retiré',
            'data' => [
                'is_liked' => false,
                'likes_count' => $likesCount
            ]
        ]);
    }

    public function getComments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commentable_type' => 'required|in:track,post,playlist',
            'commentable_id' => 'required|integer',
            'sort' => 'sometimes|in:recent,oldest,popular',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $commentableType = $this->getModelClass($request->commentable_type);
        $commentable = $commentableType::findOrFail($request->commentable_id);

        $query = $commentable->comments()
                           ->topLevel() // Seulement les commentaires principaux
                           ->withUser()
                           ->withReplies();

        // Tri
        $sort = $request->get('sort', 'recent');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'popular':
                $query->withCount('likes')->orderByDesc('likes_count');
                break;
            default:
                $query->recent();
        }

        $comments = $query->paginate($request->get('per_page', 15));

        // Ajouter les informations de like pour l'utilisateur connecté
        $currentUserId = $request->user()?->id;
        if ($currentUserId) {
            $comments->getCollection()->transform(function ($comment) use ($currentUserId) {
                $comment->is_liked = $comment->likes()->where('user_id', $currentUserId)->exists();
                $comment->likes_count = $comment->likes()->count();
                
                // Pour les réponses aussi
                if ($comment->replies) {
                    $comment->replies->transform(function ($reply) use ($currentUserId) {
                        $reply->is_liked = $reply->likes()->where('user_id', $currentUserId)->exists();
                        $reply->likes_count = $reply->likes()->count();
                        return $reply;
                    });
                }
                
                return $comment;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    public function storeComment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commentable_type' => 'required|in:track,post,playlist',
            'commentable_id' => 'required|integer',
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $commentableType = $this->getModelClass($request->commentable_type);
        $commentable = $commentableType::findOrFail($request->commentable_id);

        $currentUser = $request->user();

        // Si c'est une réponse, vérifier que le commentaire parent existe et appartient au même contenu
        if ($request->parent_id) {
            $parentComment = Comment::findOrFail($request->parent_id);
            if ($parentComment->commentable_type !== $commentableType || 
                $parentComment->commentable_id != $commentable->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commentaire parent invalide'
                ], 400);
            }
        }

        $comment = Comment::create([
            'user_id' => $currentUser->id,
            'commentable_type' => $commentableType,
            'commentable_id' => $commentable->id,
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ]);

        $comment->load('user:id,name,avatar');

        return response()->json([
            'success' => true,
            'message' => 'Commentaire ajouté',
            'data' => ['comment' => $comment]
        ], 201);
    }

    public function updateComment(Request $request, Comment $comment)
    {
        // Vérifier que l'utilisateur est le propriétaire
        if ($request->user()->id !== $comment->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez modifier que vos propres commentaires'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $comment->update([
            'content' => $request->content
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commentaire mis à jour',
            'data' => ['comment' => $comment]
        ]);
    }

    public function destroyComment(Request $request, Comment $comment)
    {
        // Vérifier que l'utilisateur est le propriétaire
        if ($request->user()->id !== $comment->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez supprimer que vos propres commentaires'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Commentaire supprimé'
        ]);
    }

    public function getUserLikes(Request $request)
    {
        $currentUser = $request->user();
        
        $likes = Like::where('user_id', $currentUser->id)
                    ->with(['likeable' => function($morphTo) {
                        $morphTo->morphWith([
                            MusicTrack::class => ['user:id,name,avatar'],
                            MusicPost::class => ['user:id,name,avatar', 'track:id,title,artist'],
                            Playlist::class => ['user:id,name,avatar'],
                        ]);
                    }])
                    ->latest()
                    ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $likes
        ]);
    }

    public function getUserComments(Request $request)
    {
        $currentUser = $request->user();
        
        $comments = Comment::where('user_id', $currentUser->id)
                          ->with(['commentable' => function($morphTo) {
                              $morphTo->morphWith([
                                  MusicTrack::class => ['user:id,name,avatar'],
                                  MusicPost::class => ['user:id,name,avatar', 'track:id,title,artist'],
                                  Playlist::class => ['user:id,name,avatar'],
                              ]);
                          }])
                          ->latest()
                          ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    private function getModelClass($type)
    {
        $models = [
            'track' => MusicTrack::class,
            'post' => MusicPost::class,
            'playlist' => Playlist::class,
            'comment' => Comment::class,
        ];

        return $models[$type] ?? null;
    }
}