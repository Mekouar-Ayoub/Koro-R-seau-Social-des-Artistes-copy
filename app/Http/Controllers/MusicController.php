<?php
// app/Http/Controllers/MusicController.php

namespace App\Http\Controllers;

use App\Models\MusicTrack;
use App\Models\MusicGenre;
use App\Models\ListeningHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MusicController extends Controller
{
    public function index(Request $request)
    {
        $query = MusicTrack::query()->with(['user:id,name,avatar']);

        // Filtres
        if ($request->has('genre')) {
            $query->byGenre($request->genre);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Tri
        $sort = $request->get('sort', 'recent');
        switch ($sort) {
            case 'popular':
                $query->popular();
                break;
            case 'oldest':
                $query->oldest();
                break;
            case 'alphabetical':
                $query->orderBy('title');
                break;
            default:
                $query->recent();
        }

        // Seulement les morceaux publics sauf si c'est l'utilisateur connecté
        $currentUserId = $request->user()?->id;
        if (!$currentUserId || $request->get('user_id') != $currentUserId) {
            $query->public();
        }

        $tracks = $query->paginate($request->get('per_page', 20));

        // Ajouter les informations de like pour l'utilisateur connecté
        if ($currentUserId) {
            $tracks->getCollection()->transform(function ($track) use ($currentUserId) {
                $track->is_liked = $track->likes()->where('user_id', $currentUserId)->exists();
                return $track;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $tracks
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'artist' => 'required|string|max:255',
            'album' => 'nullable|string|max:255',
            'genre' => 'required|string|exists:music_genres,name',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'sometimes|boolean',
            'audio_file' => 'required|file|mimes:mp3,wav,m4a,flac|max:20480', // 20MB max
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Traitement du fichier audio
            $audioFile = $request->file('audio_file');
            $fileName = time() . '_' . Str::slug($request->title) . '.' . $audioFile->getClientOriginalExtension();
            $audioPath = $audioFile->storeAs('music', $fileName, 'public');

            // Obtenir les métadonnées du fichier
            $duration = $this->getAudioDuration(storage_path('app/public/' . $audioPath));
            $fileSize = $audioFile->getSize();

            // Traitement de l'image de couverture
            $coverPath = null;
            if ($request->hasFile('cover_image')) {
                $coverFile = $request->file('cover_image');
                $coverFileName = time() . '_cover_' . Str::slug($request->title) . '.' . $coverFile->getClientOriginalExtension();
                $coverPath = $coverFile->storeAs('covers', $coverFileName, 'public');
            }

            // Créer le morceau
            $track = MusicTrack::create([
                'user_id' => $request->user()->id,
                'title' => $request->title,
                'artist' => $request->artist,
                'album' => $request->album,
                'genre' => $request->genre,
                'duration' => $duration ?: 0,
                'file_path' => 'storage/' . $audioPath,
                'file_size' => $fileSize,
                'cover_image' => $coverPath ? 'storage/' . $coverPath : null,
                'description' => $request->description,
                'is_public' => $request->get('is_public', true),
            ]);

            $track->load('user:id,name,avatar');

            return response()->json([
                'success' => true,
                'message' => 'Morceau uploadé avec succès',
                'data' => ['track' => $track]
            ], 201);

        } catch (\Exception $e) {
            // Nettoyer les fichiers en cas d'erreur
            if (isset($audioPath) && Storage::disk('public')->exists($audioPath)) {
                Storage::disk('public')->delete($audioPath);
            }
            if (isset($coverPath) && Storage::disk('public')->exists($coverPath)) {
                Storage::disk('public')->delete($coverPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, MusicTrack $track)
    {
        // Vérifier l'accès au morceau
        if (!$track->is_public && (!$request->user() || $request->user()->id !== $track->user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce morceau n\'est pas public'
            ], 403);
        }

        $track->load([
            'user:id,name,avatar,bio',
            'likes.user:id,name,avatar',
            'comments.user:id,name,avatar'
        ]);

        // Informations supplémentaires pour l'utilisateur connecté
        $userInfo = [];
        if ($request->user()) {
            $userInfo = [
                'is_liked' => $track->likes()->where('user_id', $request->user()->id)->exists(),
                'can_edit' => $request->user()->id === $track->user_id,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'track' => $track,
                'user_info' => $userInfo
            ]
        ]);
    }

    public function update(Request $request, MusicTrack $track)
    {
        // Vérifier que l'utilisateur est le propriétaire
        if ($request->user()->id !== $track->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez modifier que vos propres morceaux'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'artist' => 'sometimes|string|max:255',
            'album' => 'nullable|string|max:255',
            'genre' => 'sometimes|string|exists:music_genres,name',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'sometimes|boolean',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Mise à jour des champs
        $track->update($request->only([
            'title', 'artist', 'album', 'genre', 'description', 'is_public'
        ]));

        // Traitement de la nouvelle image de couverture
        if ($request->hasFile('cover_image')) {
            // Supprimer l'ancienne image
            if ($track->cover_image && Storage::disk('public')->exists(str_replace('storage/', '', $track->cover_image))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $track->cover_image));
            }

            $coverFile = $request->file('cover_image');
            $coverFileName = time() . '_cover_' . Str::slug($track->title) . '.' . $coverFile->getClientOriginalExtension();
            $coverPath = $coverFile->storeAs('covers', $coverFileName, 'public');
            $track->update(['cover_image' => 'storage/' . $coverPath]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Morceau mis à jour avec succès',
            'data' => ['track' => $track->fresh(['user:id,name,avatar'])]
        ]);
    }

    public function destroy(Request $request, MusicTrack $track)
    {
        // Vérifier que l'utilisateur est le propriétaire
        if ($request->user()->id !== $track->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez supprimer que vos propres morceaux'
            ], 403);
        }

        try {
            // Supprimer les fichiers
            if ($track->file_path && Storage::disk('public')->exists(str_replace('storage/', '', $track->file_path))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $track->file_path));
            }

            if ($track->cover_image && Storage::disk('public')->exists(str_replace('storage/', '', $track->cover_image))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $track->cover_image));
            }

            // Supprimer le morceau (les relations seront supprimées via cascade)
            $track->delete();

            return response()->json([
                'success' => true,
                'message' => 'Morceau supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stream(Request $request, MusicTrack $track)
    {
        // Vérifier l'accès
        if (!$track->is_public && (!$request->user() || $request->user()->id !== $track->user_id)) {
            abort(403, 'Accès non autorisé');
        }

        $filePath = storage_path('app/public/' . str_replace('storage/', '', $track->file_path));

        if (!file_exists($filePath)) {
            abort(404, 'Fichier audio non trouvé');
        }

        return response()->file($filePath, [
            'Content-Type' => 'audio/mpeg',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    public function recordPlay(Request $request, MusicTrack $track)
    {
        $validator = Validator::make($request->all(), [
            'duration_listened' => 'required|integer|min:1',
            'completed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $durationListened = $request->duration_listened;
        $completed = $request->get('completed', false);

        // Enregistrer dans l'historique
        ListeningHistory::create([
            'user_id' => $user->id,
            'track_id' => $track->id,
            'listened_at' => now(),
            'duration_listened' => $durationListened,
            'completed' => $completed,
        ]);

        // Incrémenter le compteur de lecture si écoute significative (>30 secondes ou >25% du morceau)
        if ($durationListened >= 30 || ($track->duration > 0 && $durationListened / $track->duration >= 0.25)) {
            $track->incrementPlayCount();
        }

        return response()->json([
            'success' => true,
            'message' => 'Écoute enregistrée'
        ]);
    }

    public function genres()
    {
        $genres = MusicGenre::withCount('tracks')
                           ->orderBy('tracks_count', 'desc')
                           ->get();

        return response()->json([
            'success' => true,
            'data' => ['genres' => $genres]
        ]);
    }

    public function tracksByGenre(Request $request, $genre)
    {
        $tracks = MusicTrack::byGenre($genre)
                          ->public()
                          ->with('user:id,name,avatar')
                          ->recent()
                          ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $tracks
        ]);
    }

    private function getAudioDuration($filePath)
    {
        try {
            // Utiliser getID3 ou une autre library pour obtenir la durée
            // Pour l'instant, retourner null (à implémenter selon les besoins)
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}