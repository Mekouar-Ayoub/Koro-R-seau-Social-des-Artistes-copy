<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MusicController;
use App\Http\Controllers\MusicPostController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\ListeningHistoryController;
use App\Http\Controllers\LocationController;

// Route de test simple
Route::get('/test', function () {
    return response()->json([
        'message' => 'Kore API is running!',
        'version' => '1.0.0',
        'timestamp' => now(),
        'status' => 'OK'
    ]);
});

// Routes d'authentification publiques
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Routes protégées par l'authentification
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentification
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Routes pour les utilisateurs
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/search', [UserController::class, 'search']);
    });

    // Routes pour la musique
    Route::prefix('music')->group(function () {
        Route::get('/tracks', [MusicController::class, 'index']);
        Route::get('/genres', [MusicController::class, 'genres']);
        
        // Posts musicaux
        Route::get('/posts', [MusicPostController::class, 'index']);
        Route::get('/posts/feed', [MusicPostController::class, 'feed']);
    });

    // Routes pour les playlists
    Route::prefix('playlists')->group(function () {
        Route::get('/', [PlaylistController::class, 'index']);
    });
});