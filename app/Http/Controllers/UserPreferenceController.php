<?php
// app/Http/Controllers/UserPreferenceController.php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use App\Models\MusicGenre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserPreferenceController extends Controller
{
    public function show(Request $request)
    {
        $currentUser = $request->user();
        
        $preferences = $currentUser->preferences;

        if (!$preferences) {
            // Créer des préférences par défaut si elles n'existent pas
            $preferences = UserPreference::create([
                'user_id' => $currentUser->id,
                'preferred_genres' => [],
                'auto_location' => true,
                'public_profile' => true,
                'email_notifications' => true,
                'push_notifications' => true,
            ]);
        }

        // Ajouter la liste des genres disponibles
        $availableGenres = MusicGenre::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'preferences' => $preferences,
                'available_genres' => $availableGenres
            ]
        ]);
    }

    public function update(Request $request)
    {
        $currentUser = $request->user();
        
        $validator = Validator::make($request->all(), [
            'preferred_genres' => 'sometimes|array',
            'preferred_genres.*' => 'string|exists:music_genres,name',
            'auto_location' => 'sometimes|boolean',
            'public_profile' => 'sometimes|boolean',
            'email_notifications' => 'sometimes|boolean',
            'push_notifications' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $preferences = $currentUser->preferences;

        if (!$preferences) {
            // Créer les préférences si elles n'existent pas
            $preferences = UserPreference::create([
                'user_id' => $currentUser->id,
                'preferred_genres' => $request->get('preferred_genres', []),
                'auto_location' => $request->get('auto_location', true),
                'public_profile' => $request->get('public_profile', true),
                'email_notifications' => $request->get('email_notifications', true),
                'push_notifications' => $request->get('push_notifications', true),
            ]);
        } else {
            // Mettre à jour les préférences existantes
            $updateData = $request->only([
                'preferred_genres',
                'auto_location',
                'public_profile',
                'email_notifications',
                'push_notifications'
            ]);

            $preferences->update($updateData);
        }

        // Si le profil devient privé, mettre à jour l'utilisateur
        if ($request->has('public_profile')) {
            $currentUser->update([
                'is_private' => !$request->boolean('public_profile')
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Préférences mises à jour avec succès',
            'data' => ['preferences' => $preferences->fresh()]
        ]);
    }

    public function updateGenres(Request $request)
    {
        $currentUser = $request->user();
        
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:add,remove,set',
            'genre' => 'required_if:action,add,remove|string|exists:music_genres,name',
            'genres' => 'required_if:action,set|array',
            'genres.*' => 'string|exists:music_genres,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $preferences = $currentUser->preferences;

        if (!$preferences) {
            $preferences = UserPreference::create([
                'user_id' => $currentUser->id,
                'preferred_genres' => [],
                'auto_location' => true,
                'public_profile' => true,
                'email_notifications' => true,
                'push_notifications' => true,
            ]);
        }

        $action = $request->action;

        switch ($action) {
            case 'add':
                $preferences->addGenre($request->genre);
                $message = "Genre '{$request->genre}' ajouté aux préférences";
                break;

            case 'remove':
                $preferences->removeGenre($request->genre);
                $message = "Genre '{$request->genre}' retiré des préférences";
                break;

            case 'set':
                $preferences->setGenres($request->genres);
                $message = 'Genres préférés mis à jour';
                break;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => ['preferred_genres' => $preferences->fresh()->preferred_genres]
        ]);
    }

    public function getRecommendations(Request $request)
    {
        $currentUser = $request->user();
        $preferences = $currentUser->preferences;

        if (!$preferences || empty($preferences->preferred_genres)) {
            return response()->json([
                'success' => true,
                'message' => 'Aucune préférence définie pour les recommandations',
                'data' => [
                    'tracks' => [],
                    'users' => [],
                    'playlists' => []
                ]
            ]);
        }

        $preferredGenres = $preferences->preferred_genres;

        // Recommandations de morceaux
        $recommendedTracks = \App\Models\MusicTrack::public()
                                                  ->whereIn('genre', $preferredGenres)
                                                  ->where('user_id', '!=', $currentUser->id)
                                                  ->with('user:id,name,avatar')
                                                  ->popular()
                                                  ->take(10)
                                                  ->get();

        // Recommandations d'utilisateurs avec des goûts similaires
        $recommendedUsers = \App\Models\User::whereHas('preferences', function($query) use ($preferredGenres) {
                                                $query->where(function($q) use ($preferredGenres) {
                                                    foreach ($preferredGenres as $genre) {
                                                        $q->orWhereJsonContains('preferred_genres', $genre);
                                                    }
                                                });
                                            })
                                            ->where('id', '!=', $currentUser->id)
                                            ->where('is_private', false)
                                            ->whereDoesntHave('followers', function($query) use ($currentUser) {
                                                $query->where('follower_id', $currentUser->id);
                                            })
                                            ->with('preferences')
                                            ->take(8)
                                            ->get();

        // Recommandations de playlists
        $recommendedPlaylists = \App\Models\Playlist::public()
                                                   ->whereHas('tracks', function($query) use ($preferredGenres) {
                                                       $query->whereIn('genre', $preferredGenres);
                                                   })
                                                   ->where('user_id', '!=', $currentUser->id)
                                                   ->with('user:id,name,avatar')
                                                   ->withCount('tracks')
                                                   ->take(6)
                                                   ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'tracks' => $recommendedTracks,
                'users' => $recommendedUsers,
                'playlists' => $recommendedPlaylists,
                'based_on_genres' => $preferredGenres
            ]
        ]);
    }

    public function exportPreferences(Request $request)
    {
        $currentUser = $request->user();
        $preferences = $currentUser->preferences;

        $exportData = [
            'user' => [
                'name' => $currentUser->name,
                'email' => $currentUser->email,
                'exported_at' => now()->toISOString()
            ],
            'preferences' => $preferences ? $preferences->toArray() : null,
            'stats' => [
                'tracks_count' => $currentUser->musicTracks()->count(),
                'playlists_count' => $currentUser->playlists()->count(),
                'followers_count' => $currentUser->followers()->count(),
                'following_count' => $currentUser->following()->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $exportData
        ]);
    }
}