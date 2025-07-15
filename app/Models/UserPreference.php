<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'preferred_genres',
        'auto_location',
        'public_profile',
        'email_notifications',
        'push_notifications',
    ];

    protected $casts = [
        'preferred_genres' => 'array',
        'auto_location' => 'boolean',
        'public_profile' => 'boolean',
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Méthodes utiles
    public function getCompletionPercentageAttribute()
    {
        if ($this->track && $this->track->duration > 0) {
            return round(($this->duration_listened / $this->track->duration) * 100, 2);
        }
        return 0;
    }

    public function wasSkipped()
    {
        return !$this->completed && $this->completion_percentage < 80;
    }

    public function wasCompleted()
    {
        return $this->completed || $this->completion_percentage >= 80;
    }

    public function hasGenre($genre)
    {
        return in_array($genre, $this->preferred_genres ?? []);
    }

    public function addGenre($genre)
    {
        $genres = $this->preferred_genres ?? [];
        if (!in_array($genre, $genres)) {
            $genres[] = $genre;
            $this->update(['preferred_genres' => $genres]);
        }
    }

    public function removeGenre($genre)
    {
        $genres = $this->preferred_genres ?? [];
        $genres = array_filter($genres, function ($g) use ($genre) {
            return $g !== $genre;
        });
        $this->update(['preferred_genres' => array_values($genres)]);
    }

    public function setGenres(array $genres)
    {
        $this->update(['preferred_genres' => $genres]);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTrack($query, $trackId)
    {
        return $query->where('track_id', $trackId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('listened_at', 'desc');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('listened_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('listened_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('listened_at', now()->month)
            ->whereYear('listened_at', now()->year);
    }

    public function scopeWithTrackAndUser($query)
    {
        return $query->with(['track:id,title,artist,duration', 'user:id,name,avatar']);
    }

    // Méthodes statiques pour les statistiques
    public static function getUserStats($userId, $period = 'week')
    {
        $query = static::where('user_id', $userId);

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

        $totalListenings = $query->count();
        $totalDuration = $query->sum('duration_listened');
        $uniqueTracks = $query->distinct('track_id')->count();
        $completedListenings = $query->completed()->count();

        return [
            'total_listenings' => $totalListenings,
            'total_duration_seconds' => $totalDuration,
            'total_duration_formatted' => static::formatDuration($totalDuration),
            'unique_tracks' => $uniqueTracks,
            'completed_listenings' => $completedListenings,
            'completion_rate' => $totalListenings > 0 ? round(($completedListenings / $totalListenings) * 100, 2) : 0,
        ];
    }

    public static function getTopTracks($userId, $limit = 10, $period = 'month')
    {
        $query = static::where('user_id', $userId);

        switch ($period) {
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
        }

        return $query->select('track_id', \DB::raw('COUNT(*) as play_count'), \DB::raw('SUM(duration_listened) as total_duration'))
            ->groupBy('track_id')
            ->orderBy('play_count', 'desc')
            ->orderBy('total_duration', 'desc')
            ->limit($limit)
            ->with('track')
            ->get();
    }

    public static function getTopGenres($userId, $limit = 5, $period = 'month')
    {
        $query = static::where('user_id', $userId)
            ->join('music_tracks', 'listening_history.track_id', '=', 'music_tracks.id');

        switch ($period) {
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
        }

        return $query->select('music_tracks.genre', \DB::raw('COUNT(*) as play_count'), \DB::raw('SUM(duration_listened) as total_duration'))
            ->groupBy('music_tracks.genre')
            ->orderBy('play_count', 'desc')
            ->orderBy('total_duration', 'desc')
            ->limit($limit)
            ->get();
    }

    private static function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $remainingSeconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $remainingSeconds);
        } else {
            return sprintf('%ds', $remainingSeconds);
        }
    }
}
