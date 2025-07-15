<?php
// app/Http/Controllers/ListeningHistoryController.php

namespace App\Http\Controllers;

use App\Models\ListeningHistory;
use App\Models\MusicTrack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ListeningHistoryController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        
        $query = ListeningHistory::where('user_id', $currentUser->id)
                                ->withTrackAndUser();

        // Filtres
        if ($request->has('period')) {
            $period = $request->period;
            switch ($period) {
                case 'today':
                    $query->today();
                    break;
                case 'week':
                    $query->thisWeek();
                    break;
                case 'month':
                    $query->thisMonth();
                    break;
            }
        }

        if ($request->has('completed_only') && $request->boolean('completed_only')) {
            $query->completed();
        }

        if ($request->has('track_id')) {
            $query->byTrack($request->track_id);
        }

        $history = $query->recent()
                        ->paginate($request->get('per_page', 20));

        // Ajouter des informations calculées
        $history->getCollection()->transform(function ($entry) {
            $entry->completion_percentage = $entry->completion_percentage;
            $entry->was_skipped = $entry->wasSkipped();
            $entry->was_completed = $entry->wasCompleted();
            return $entry;
        });

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'track_id' => 'required|exists:music_tracks,id',
            'duration_listened' => 'required|integer|min:1',
            'completed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $currentUser = $request->user();
        $track = MusicTrack::findOrFail($request->track_id);

        // Vérifier l'accès au morceau
        if (!$track->is_public && $track->user_id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à ce morceau'
            ], 403);
        }

        $durationListened = $request->duration_listened;
        $completed = $request->get('completed', false);

        // Auto-détection de la completion si pas spécifiée
        if (!$request->has('completed') && $track->duration > 0) {
            $completed = ($durationListened / $track->duration) >= 0.8; // 80% = completed
        }

        $historyEntry = ListeningHistory::create([
            'user_id' => $currentUser->id,
            'track_id' => $track->id,
            'listened_at' => now(),
            'duration_listened' => $durationListened,
            'completed' => $completed,
        ]);

        // Incrémenter le compteur de lecture si écoute significative
        if ($durationListened >= 30 || ($track->duration > 0 && $durationListened / $track->duration >= 0.25)) {
            $track->incrementPlayCount();
        }

        return response()->json([
            'success' => true,
            'message' => 'Écoute enregistrée',
            'data' => ['entry' => $historyEntry]
        ], 201);
    }

    public function stats(Request $request)
    {
        $currentUser = $request->user();
        $period = $request->get('period', 'week'); // today, week, month, all

        // Statistiques générales
        $generalStats = ListeningHistory::getUserStats($currentUser->id, $period);

        // Top morceaux
        $topTracks = ListeningHistory::getTopTracks($currentUser->id, 10, $period);

        // Top genres
        $topGenres = ListeningHistory::getTopGenres($currentUser->id, 5, $period);

        // Statistiques d'écoute par jour (pour graphiques)
        $dailyStats = $this->getDailyStats($currentUser->id, $period);

        // Artistes les plus écoutés
        $topArtists = $this->getTopArtists($currentUser->id, $period);

        // Heures d'écoute préférées
        $listeningPatterns = $this->getListeningPatterns($currentUser->id, $period);

        return response()->json([
            'success' => true,
            'data' => [
                'general' => $generalStats,
                'top_tracks' => $topTracks,
                'top_genres' => $topGenres,
                'top_artists' => $topArtists,
                'daily_stats' => $dailyStats,
                'listening_patterns' => $listeningPatterns,
                'period' => $period
            ]
        ]);
    }

    public function getRecentlyPlayed(Request $request)
    {
        $currentUser = $request->user();
        
        $recentTracks = ListeningHistory::where('user_id', $currentUser->id)
                                       ->with('track.user:id,name,avatar')
                                       ->recent()
                                       ->take($request->get('limit', 20))
                                       ->get()
                                       ->unique('track_id') // Éviter les doublons
                                       ->values();

        return response()->json([
            'success' => true,
            'data' => ['tracks' => $recentTracks]
        ]);
    }

    public function getMostPlayed(Request $request)
    {
        $currentUser = $request->user();
        $period = $request->get('period', 'month');
        
        $query = ListeningHistory::where('user_id', $currentUser->id);

        // Appliquer le filtre de période
        switch ($period) {
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
        }

        $mostPlayed = $query->select('track_id', DB::raw('COUNT(*) as play_count'))
                           ->groupBy('track_id')
                           ->orderByDesc('play_count')
                           ->with('track.user:id,name,avatar')
                           ->take($request->get('limit', 10))
                           ->get();

        return response()->json([
            'success' => true,
            'data' => ['tracks' => $mostPlayed]
        ]);
    }

    public function getListeningStreak(Request $request)
    {
        $currentUser = $request->user();
        
        // Calculer la streak d'écoute (jours consécutifs avec au moins une écoute)
        $streak = $this->calculateListeningStreak($currentUser->id);
        
        // Statistiques de streak
        $streakStats = [
            'current_streak' => $streak['current'],
            'longest_streak' => $streak['longest'],
            'last_listened' => ListeningHistory::where('user_id', $currentUser->id)
                                             ->latest('listened_at')
                                             ->value('listened_at'),
        ];

        return response()->json([
            'success' => true,
            'data' => $streakStats
        ]);
    }

    private function getDailyStats($userId, $period)
    {
        $days = match($period) {
            'today' => 1,
            'week' => 7,
            'month' => 30,
            default => 30
        };

        $stats = ListeningHistory::where('user_id', $userId)
                                ->where('listened_at', '>=', now()->subDays($days))
                                ->selectRaw('DATE(listened_at) as date, COUNT(*) as plays, SUM(duration_listened) as total_duration')
                                ->groupBy('date')
                                ->orderBy('date')
                                ->get();

        return $stats;
    }

    private function getTopArtists($userId, $period)
    {
        $query = ListeningHistory::where('user_id', $userId)
                                ->join('music_tracks', 'listening_history.track_id', '=', 'music_tracks.id');

        switch ($period) {
            case 'week':
                $query->where('listening_history.listened_at', '>=', now()->subWeek());
                break;
            case 'month':
                $query->where('listening_history.listened_at', '>=', now()->subMonth());
                break;
        }

        return $query->select('music_tracks.artist', DB::raw('COUNT(*) as play_count'))
                    ->groupBy('music_tracks.artist')
                    ->orderByDesc('play_count')
                    ->take(10)
                    ->get();
    }

    private function getListeningPatterns($userId, $period)
    {
        $query = ListeningHistory::where('user_id', $userId);

        switch ($period) {
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
        }

        // Répartition par heure de la journée
        $hourlyPattern = $query->selectRaw('HOUR(listened_at) as hour, COUNT(*) as plays')
                              ->groupBy('hour')
                              ->orderBy('hour')
                              ->get();

        // Répartition par jour de la semaine
        $weeklyPattern = $query->selectRaw('DAYOFWEEK(listened_at) as day, COUNT(*) as plays')
                              ->groupBy('day')
                              ->orderBy('day')
                              ->get();

        return [
            'hourly' => $hourlyPattern,
            'weekly' => $weeklyPattern
        ];
    }

    private function calculateListeningStreak($userId)
    {
        $dates = ListeningHistory::where('user_id', $userId)
                                ->selectRaw('DATE(listened_at) as date')
                                ->distinct()
                                ->orderByDesc('date')
                                ->pluck('date')
                                ->toArray();

        $currentStreak = 0;
        $longestStreak = 0;
        $tempStreak = 0;

        if (empty($dates)) {
            return ['current' => 0, 'longest' => 0];
        }

        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        // Vérifier si l'utilisateur a écouté aujourd'hui ou hier
        if ($dates[0] === $today || $dates[0] === $yesterday) {
            $currentStreak = 1;
            $currentDate = $dates[0];

            for ($i = 1; $i < count($dates); $i++) {
                $expectedDate = date('Y-m-d', strtotime($currentDate . ' -1 day'));
                
                if ($dates[$i] === $expectedDate) {
                    $currentStreak++;
                    $currentDate = $dates[$i];
                } else {
                    break;
                }
            }
        }

        // Calculer la plus longue streak
        for ($i = 0; $i < count($dates); $i++) {
            $tempStreak = 1;
            $currentDate = $dates[$i];

            for ($j = $i + 1; $j < count($dates); $j++) {
                $expectedDate = date('Y-m-d', strtotime($currentDate . ' -1 day'));
                
                if ($dates[$j] === $expectedDate) {
                    $tempStreak++;
                    $currentDate = $dates[$j];
                } else {
                    break;
                }
            }

            $longestStreak = max($longestStreak, $tempStreak);
        }

        return [
            'current' => $currentStreak,
            'longest' => $longestStreak
        ];
    }
}