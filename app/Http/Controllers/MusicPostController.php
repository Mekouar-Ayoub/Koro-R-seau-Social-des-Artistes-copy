<?php
// app/Http/Controllers/MusicPostController.php

namespace App\Http\Controllers;

use App\Models\MusicPost;
use App\Models\MusicTrack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MusicPostController extends Controller
{
    public function index(Request $request)
    {
        $query = MusicPost::query()
                         ->with([
                             'user:id,name,avatar',
                             'track:id,title,artist,duration,cover_image'
                         ]);

        // Filtres
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('genre')) {
            $query->whereHas('track', function($q) use ($request) {
                $q->where('genre', $request->genre);
            });
        }

        if ($request->has('with_location')) {
            if ($request->boolean('with_location')) {
                $query->withLocation()->publicLocation();
            } else {
                $query->whereNull('latitude');
            }
        }

        $posts = $query->recent()
                      ->paginate($request->get('per_page', 20));

        // Ajouter les informations de like pour l'utilisateur connecté
        $currentUserId = $request->user()?->id;
        if ($currentUserId) {
            $posts->getCollection()->transform(function ($post) use ($currentUserId) {
                $post->is_liked = $post->likes()->where('user_id', $currentUserId)->exists();
                $post->comments_count = $post->comments()->count();
                $post->likes_count = $post->likes()->count();
                return $post;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'track_id' => 'required|exists:music_tracks,id',
            'content' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
            'location_type' => 'nullable|in:automatic,manual',
            'is_location_public' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier que le morceau appartient à l'utilisateur ou est public
        $track = MusicTrack::findOrFail($request->track_id);
        $currentUser = $request->user();
        
        if (!$track->is_public && $track->user_id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas créer un post avec ce morceau'
            ], 403);
        }

        // Validation de la géolocalisation
        $hasLocation = $request->filled('latitude') && $request->filled('longitude');
        if ($hasLocation) {
            if (!$request->filled('location_type')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le type de localisation est requis'
                ], 422);
            }
        }

        $post = MusicPost::create([
            'user_id' => $currentUser->id,
            'track_id' => $request->track_id,
            'content' => $request->content,
            'latitude' => $hasLocation ? $request->latitude : null,
            'longitude' => $hasLocation ? $request->longitude : null,
            'location_name' => $request->location_name,
            'location_type' => $hasLocation ? $request->location_type : null,
            'is_location_public' => $request->get('is_location_public', true),
        ]);

        $post->load([
            'user:id,name,avatar',
            'track:id,title,artist,duration,cover_image'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post créé avec succès',
            'data' => ['post' => $post]
        ], 201);
    }

    public function show(Request $request, MusicPost $post)
    {
        $post->load([
            'user:id,name,avatar,bio',
            'track:id,title,artist,duration,cover_image,genre',
            'comments.user:id,name,avatar',
            'comments.replies.user:id,name,avatar'
        ]);

        // Informations pour l'utilisateur connecté
        $userInfo = [];
        if ($request->user()) {
            $userInfo = [
                'is_liked' => $post->likes()->where('user_id', $request->user()->id)->exists(),
                'can_edit' => $request->user()->id === $post->user_id,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'post' => $post,
                'user_info' => $userInfo
            ]
        ]);
    }

    public function update(Request $request, MusicPost $post)
    {
        // Vérifier que l'utilisateur est le propriétaire
        if ($request->user()->id !== $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez modifier que vos propres posts'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
            'location_type' => 'nullable|in:automatic,manual',
            'is_location_public' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validation de la géolocalisation
        $hasLocation = $request->filled('latitude') && $request->filled('longitude');
        if ($hasLocation && !$request->filled('location_type')) {
            return response()->json([
                'success' => false,
                'message' => 'Le type de localisation est requis'
            ], 422);
        }

        $post->update([
            'content' => $request->content,
            'latitude' => $hasLocation ? $request->latitude : null,
            'longitude' => $hasLocation ? $request->longitude : null,
            'location_name' => $request->location_name,
            'location_type' => $hasLocation ? $request->location_type : null,
            'is_location_public' => $request->get('is_location_public', $post->is_location_public),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post mis à jour avec succès',
            'data' => ['post' => $post->fresh(['user:id,name,avatar', 'track:id,title,artist,duration,cover_image'])]
        ]);
    }

    public function destroy(Request $request, MusicPost $post)
    {
        // Vérifier que l'utilisateur est le propriétaire
        if ($request->user()->id !== $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez supprimer que vos propres posts'
            ], 403);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post supprimé avec succès'
        ]);
    }

    public function nearby(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'sometimes|numeric|min:0.1|max:100', // en km
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->get('radius', 10); // 10km par défaut

        $posts = MusicPost::nearby($latitude, $longitude, $radius)
                         ->publicLocation()
                         ->with([
                             'user:id,name,avatar',
                             'track:id,title,artist,duration,cover_image'
                         ])
                         ->recent()
                         ->paginate($request->get('per_page', 20));

        // Ajouter la distance à chaque post
        $posts->getCollection()->transform(function ($post) use ($latitude, $longitude) {
            $post->distance_km = round($post->distance, 2);
            unset($post->distance); // Supprimer le champ distance brut
            return $post;
        });

        return response()->json([
            'success' => true,
            'data' => $posts,
            'search_center' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius_km' => $radius
            ]
        ]);
    }

    public function feed(Request $request)
    {
        $currentUser = $request->user();
        
        // Récupérer les posts des utilisateurs suivis
        $followingIds = $currentUser->following()->pluck('id');
        
        $query = MusicPost::query()
                         ->with([
                             'user:id,name,avatar',
                             'track:id,title,artist,duration,cover_image'
                         ]);

        if ($followingIds->isNotEmpty()) {
            // Posts des utilisateurs suivis + propres posts
            $query->whereIn('user_id', $followingIds->merge([$currentUser->id]));
        } else {
            // Si l'utilisateur ne suit personne, montrer seulement ses posts
            $query->where('user_id', $currentUser->id);
        }

        $posts = $query->recent()
                      ->paginate($request->get('per_page', 20));

        // Ajouter les informations d'interaction
        $posts->getCollection()->transform(function ($post) use ($currentUser) {
            $post->is_liked = $post->likes()->where('user_id', $currentUser->id)->exists();
            $post->comments_count = $post->comments()->count();
            $post->likes_count = $post->likes()->count();
            return $post;
        });

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    public function discover(Request $request)
    {
        $currentUser = $request->user();
        
        // Récupérer les genres préférés de l'utilisateur
        $preferredGenres = $currentUser->preferences?->preferred_genres ?? [];
        
        $query = MusicPost::query()
                         ->with([
                             'user:id,name,avatar',
                             'track:id,title,artist,duration,cover_image,genre'
                         ]);

        // Exclure les propres posts
        $query->where('user_id', '!=', $currentUser->id);

        // Filtrer par genres préférés si disponibles
        if (!empty($preferredGenres)) {
            $query->whereHas('track', function($q) use ($preferredGenres) {
                $q->whereIn('genre', $preferredGenres);
            });
        }

        // Favoriser les posts populaires récents
        $posts = $query->withCount(['likes', 'comments'])
                      ->orderByDesc('likes_count')
                      ->orderByDesc('comments_count')
                      ->recent()
                      ->paginate($request->get('per_page', 20));

        // Ajouter les informations d'interaction
        $posts->getCollection()->transform(function ($post) use ($currentUser) {
            $post->is_liked = $post->likes()->where('user_id', $currentUser->id)->exists();
            return $post;
        });

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    public function trending(Request $request)
    {
        // Posts tendances basés sur l'activité récente
        $posts = MusicPost::query()
                         ->with([
                             'user:id,name,avatar',
                             'track:id,title,artist,duration,cover_image'
                         ])
                         ->withCount(['likes', 'comments'])
                         ->where('created_at', '>=', now()->subDays(7)) // Dernière semaine
                         ->having('likes_count', '>', 2) // Au moins 3 likes
                         ->orderByDesc('likes_count')
                         ->orderByDesc('comments_count')
                         ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }
}