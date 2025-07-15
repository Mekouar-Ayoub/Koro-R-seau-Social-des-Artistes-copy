<?php
// app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Follow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with(['preferences']);

        // Filtres
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('bio', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->has('genre')) {
            $query->whereHas('preferences', function($q) use ($request) {
                $q->whereJsonContains('preferred_genres', $request->genre);
            });
        }

        if ($request->has('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        // Exclure les profils privés sauf si c'est l'utilisateur connecté
        $currentUserId = $request->user()?->id;
        if ($currentUserId) {
            $query->where(function($q) use ($currentUserId) {
                $q->where('is_private', false)
                  ->orWhere('id', $currentUserId);
            });
        } else {
            $query->where('is_private', false);
        }

        $users = $query->latest()
                      ->paginate($request->get('per_page', 15));

        // Ajouter des infos supplémentaires si utilisateur connecté
        if ($currentUserId) {
            $users->getCollection()->transform(function ($user) use ($currentUserId) {
                $user->is_following = $user->id !== $currentUserId ? 
                    Follow::where('follower_id', $currentUserId)
                          ->where('following_id', $user->id)
                          ->exists() : false;
                $user->is_followed_by = $user->id !== $currentUserId ?
                    Follow::where('follower_id', $user->id)
                          ->where('following_id', $currentUserId)
                          ->exists() : false;
                return $user;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function show(Request $request, User $user)
    {
        // Vérifier si le profil est accessible
        $currentUser = $request->user();
        
        if ($user->is_private && (!$currentUser || $currentUser->id !== $user->id)) {
            // Vérifier si l'utilisateur connecté suit cette personne
            $isFollowing = $currentUser ? 
                Follow::where('follower_id', $currentUser->id)
                      ->where('following_id', $user->id)
                      ->exists() : false;
                      
            if (!$isFollowing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce profil est privé'
                ], 403);
            }
        }

        $user->load([
            'preferences',
            'musicTracks' => function($query) {
                $query->public()->latest()->take(6);
            },
            'playlists' => function($query) {
                $query->public()->latest()->take(4);
            }
        ]);

        // Statistiques
        $stats = [
            'tracks_count' => $user->musicTracks()->public()->count(),
            'playlists_count' => $user->playlists()->public()->count(),
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
            'total_plays' => $user->musicTracks()->sum('play_count'),
        ];

        // Relations avec l'utilisateur connecté
        $relations = [];
        if ($currentUser && $currentUser->id !== $user->id) {
            $relations = [
                'is_following' => Follow::where('follower_id', $currentUser->id)
                                       ->where('following_id', $user->id)
                                       ->exists(),
                'is_followed_by' => Follow::where('follower_id', $user->id)
                                         ->where('following_id', $currentUser->id)
                                         ->exists(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'stats' => $stats,
                'relations' => $relations
            ]
        ]);
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:50',
            'type' => 'sometimes|in:users,tracks,playlists,all',
            'limit' => 'sometimes|integer|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->q;
        $type = $request->get('type', 'all');
        $limit = $request->get('limit', 10);
        $results = [];

        if ($type === 'users' || $type === 'all') {
            $users = User::where('is_private', false)
                        ->where(function($q) use ($query) {
                            $q->where('name', 'like', "%{$query}%")
                              ->orWhere('bio', 'like', "%{$query}%");
                        })
                        ->with('preferences')
                        ->take($limit)
                        ->get();

            $results['users'] = $users;
        }

        if ($type === 'tracks' || $type === 'all') {
            $tracks = \App\Models\MusicTrack::public()
                                          ->search($query)
                                          ->with('user:id,name,avatar')
                                          ->take($limit)
                                          ->get();

            $results['tracks'] = $tracks;
        }

        if ($type === 'playlists' || $type === 'all') {
            $playlists = \App\Models\Playlist::public()
                                           ->search($query)
                                           ->with('user:id,name,avatar')
                                           ->take($limit)
                                           ->get();

            $results['playlists'] = $playlists;
        }

        return response()->json([
            'success' => true,
            'data' => $results,
            'query' => $query
        ]);
    }

    public function follow(Request $request, User $user)
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous suivre vous-même'
            ], 400);
        }

        $existingFollow = Follow::where('follower_id', $currentUser->id)
                               ->where('following_id', $user->id)
                               ->first();

        if ($existingFollow) {
            return response()->json([
                'success' => false,
                'message' => 'Vous suivez déjà cet utilisateur'
            ], 400);
        }

        Follow::create([
            'follower_id' => $currentUser->id,
            'following_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Vous suivez maintenant {$user->name}",
            'data' => [
                'is_following' => true,
                'followers_count' => $user->followers()->count()
            ]
        ]);
    }

    public function unfollow(Request $request, User $user)
    {
        $currentUser = $request->user();

        $follow = Follow::where('follower_id', $currentUser->id)
                       ->where('following_id', $user->id)
                       ->first();

        if (!$follow) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne suivez pas cet utilisateur'
            ], 400);
        }

        $follow->delete();

        return response()->json([
            'success' => true,
            'message' => "Vous ne suivez plus {$user->name}",
            'data' => [
                'is_following' => false,
                'followers_count' => $user->followers()->count()
            ]
        ]);
    }

    public function followers(Request $request, User $user)
    {
        // Vérifier l'accès au profil
        $currentUser = $request->user();
        
        if ($user->is_private && (!$currentUser || $currentUser->id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce profil est privé'
            ], 403);
        }

        $followers = $user->followers()
                         ->with('preferences')
                         ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $followers
        ]);
    }

    public function following(Request $request, User $user)
    {
        // Vérifier l'accès au profil
        $currentUser = $request->user();
        
        if ($user->is_private && (!$currentUser || $currentUser->id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce profil est privé'
            ], 403);
        }

        $following = $user->following()
                         ->with('preferences')
                         ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $following
        ]);
    }
}