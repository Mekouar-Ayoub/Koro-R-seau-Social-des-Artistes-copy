<?php
// app/Http/Controllers/PlaylistController.php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\MusicTrack;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlaylistController extends Controller
{
    public function index(Request $request)
    {
        $query = Playlist::query()->with(['user:id,name,avatar']);

        // Filtres
        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('collaborative')) {
            if ($request->boolean('collaborative')) {
                $query->collaborative();
            }
        }

        // Seulement les playlists publiques sauf si c'est l'utilisateur connecté
        $currentUserId = $request->user()?->id;
        if (!$currentUserId || $request->get('user_id') != $currentUserId) {
            $query->public();
        }

        $playlists = $query->withCount(['tracks', 'likes'])
                          ->latest()
                          ->paginate($request->get('per_page', 20));

        // Ajouter les informations de like pour l'utilisateur connecté
        if ($currentUserId) {
            $playlists->getCollection()->transform(function ($playlist) use ($currentUserId) {
                $playlist->is_liked = $playlist->likes()->where('user_id', $currentUserId)->exists();
                $playlist->is_collaborator = $playlist->collaborators()->where('user_id', $currentUserId)->exists();
                return $playlist;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $playlists
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'sometimes|boolean',
            'is_collaborative' => 'sometimes|boolean',
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
            // Traitement de l'image de couverture
            $coverPath = null;
            if ($request->hasFile('cover_image')) {
                $coverFile = $request->file('cover_image');
                $coverFileName = time() . '_playlist_' . Str::slug($request->name) . '.' . $coverFile->getClientOriginalExtension();
                $coverPath = $coverFile->storeAs('playlist-covers', $coverFileName, 'public');
            }

            $playlist = Playlist::create([
                'user_id' => $request->user()->id,
                'name' => $request->name,
                'description' => $request->description,
                'cover_image' => $coverPath ? 'storage/' . $coverPath : null,
                'is_public' => $request->get('is_public', true),
                'is_collaborative' => $request->get('is_collaborative', false),
            ]);

            $playlist->load('user:id,name,avatar');

            return response()->json([
                'success' => true,
                'message' => 'Playlist créée avec succès',
                'data' => ['playlist' => $playlist]
            ], 201);

        } catch (\Exception $e) {
            // Nettoyer le fichier en cas d'erreur
            if (isset($coverPath) && Storage::disk('public')->exists($coverPath)) {
                Storage::disk('public')->delete($coverPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, Playlist $playlist)
    {
        $currentUser = $request->user();

        // Vérifier l'accès à la playlist
        if (!$playlist->canView($currentUser?->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Cette playlist n\'est pas accessible'
            ], 403);
        }

        $playlist->load([
            'user:id,name,avatar,bio',
            'tracks:id,title,artist,duration,cover_image,genre',
            'tracks.user:id,name,avatar',
            'collaborators:id,name,avatar'
        ]);

        // Informations pour l'utilisateur connecté
        $userInfo = [];
        if ($currentUser) {
            $userInfo = [
                'is_liked' => $playlist->likes()->where('user_id', $currentUser->id)->exists(),
                'can_edit' => $playlist->canEdit($currentUser->id),
                'is_owner' => $currentUser->id === $playlist->user_id,
                'is_collaborator' => $playlist->collaborators()->where('user_id', $currentUser->id)->exists(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'playlist' => $playlist,
                'user_info' => $userInfo
            ]
        ]);
    }

    public function update(Request $request, Playlist $playlist)
    {
        $currentUser = $request->user();

        // Vérifier les permissions
        if (!$playlist->canEdit($currentUser->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de modifier cette playlist'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'sometimes|boolean',
            'is_collaborative' => 'sometimes|boolean',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Seul le propriétaire peut changer la visibilité et la collaboration
        $updateData = $request->only(['name', 'description']);
        if ($currentUser->id === $playlist->user_id) {
            $updateData = array_merge($updateData, $request->only(['is_public', 'is_collaborative']));
        }

        $playlist->update($updateData);

        // Traitement de la nouvelle image de couverture
        if ($request->hasFile('cover_image')) {
            // Supprimer l'ancienne image
            if ($playlist->cover_image && Storage::disk('public')->exists(str_replace('storage/', '', $playlist->cover_image))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $playlist->cover_image));
            }

            $coverFile = $request->file('cover_image');
            $coverFileName = time() . '_playlist_' . Str::slug($playlist->name) . '.' . $coverFile->getClientOriginalExtension();
            $coverPath = $coverFile->storeAs('playlist-covers', $coverFileName, 'public');
            $playlist->update(['cover_image' => 'storage/' . $coverPath]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Playlist mise à jour avec succès',
            'data' => ['playlist' => $playlist->fresh(['user:id,name,avatar'])]
        ]);
    }

    public function destroy(Request $request, Playlist $playlist)
    {
        // Seul le propriétaire peut supprimer
        if ($request->user()->id !== $playlist->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le propriétaire peut supprimer cette playlist'
            ], 403);
        }

        try {
            // Supprimer l'image de couverture
            if ($playlist->cover_image && Storage::disk('public')->exists(str_replace('storage/', '', $playlist->cover_image))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $playlist->cover_image));
            }

            $playlist->delete();

            return response()->json([
                'success' => true,
                'message' => 'Playlist supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addTrack(Request $request, Playlist $playlist)
    {
        $currentUser = $request->user();

        if (!$playlist->canEdit($currentUser->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de modifier cette playlist'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'track_id' => 'required|exists:music_tracks,id',
            'position' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $track = MusicTrack::findOrFail($request->track_id);

        // Vérifier que le morceau est accessible
        if (!$track->is_public && $track->user_id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ce morceau n\'est pas accessible'
            ], 403);
        }

        // Vérifier si le morceau n'est pas déjà dans la playlist
        if ($playlist->tracks()->where('track_id', $track->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce morceau est déjà dans la playlist'
            ], 400);
        }

        $position = $request->position ?? ($playlist->playlistTracks()->max('position') + 1);

        $playlist->addTrack($track->id, $currentUser->id, $position);

        return response()->json([
            'success' => true,
            'message' => 'Morceau ajouté à la playlist',
            'data' => [
                'track' => $track,
                'position' => $position
            ]
        ]);
    }

    public function removeTrack(Request $request, Playlist $playlist, MusicTrack $track)
    {
        $currentUser = $request->user();

        if (!$playlist->canEdit($currentUser->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de modifier cette playlist'
            ], 403);
        }

        if (!$playlist->removeTrack($track->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce morceau n\'est pas dans cette playlist'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Morceau retiré de la playlist'
        ]);
    }

    public function reorderTracks(Request $request, Playlist $playlist)
    {
        $currentUser = $request->user();

        if (!$playlist->canEdit($currentUser->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de modifier cette playlist'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'track_ids' => 'required|array',
            'track_ids.*' => 'exists:music_tracks,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $playlist->reorderTracks($request->track_ids);

        return response()->json([
            'success' => true,
            'message' => 'Ordre des morceaux mis à jour'
        ]);
    }

    public function addCollaborator(Request $request, Playlist $playlist)
    {
        // Seul le propriétaire peut ajouter des collaborateurs
        if ($request->user()->id !== $playlist->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le propriétaire peut ajouter des collaborateurs'
            ], 403);
        }

        if (!$playlist->is_collaborative) {
            return response()->json([
                'success' => false,
                'message' => 'Cette playlist n\'est pas collaborative'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $collaborator = User::findOrFail($request->user_id);

        if ($collaborator->id === $playlist->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Le propriétaire est déjà collaborateur'
            ], 400);
        }

        if ($playlist->collaborators()->where('user_id', $collaborator->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur est déjà collaborateur'
            ], 400);
        }

        $playlist->collaborators()->attach($collaborator->id, [
            'role' => 'collaborator',
            'invited_at' => now(),
            'accepted_at' => now(), // Auto-accepté pour simplifier
        ]);

        return response()->json([
            'success' => true,
            'message' => "Collaborateur {$collaborator->name} ajouté",
            'data' => ['collaborator' => $collaborator->only(['id', 'name', 'avatar'])]
        ]);
    }

    public function removeCollaborator(Request $request, Playlist $playlist, User $user)
    {
        // Seul le propriétaire peut retirer des collaborateurs
        if ($request->user()->id !== $playlist->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le propriétaire peut retirer des collaborateurs'
            ], 403);
        }

        if (!$playlist->collaborators()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur n\'est pas collaborateur'
            ], 400);
        }

        $playlist->collaborators()->detach($user->id);

        return response()->json([
            'success' => true,
            'message' => "Collaborateur {$user->name} retiré"
        ]);
    }
}