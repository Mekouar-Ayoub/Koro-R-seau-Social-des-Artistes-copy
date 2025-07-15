<?php
// app/Models/MusicPost.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class MusicPost extends Model
{
    use HasFactory, HasSpatial;

    protected $fillable = [
        'user_id',
        'track_id',
        'content',
        'latitude',
        'longitude',
        'location_name',
        'location_type',
        'is_location_public',
    ];

    protected $casts = [
        'is_location_public' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function track()
    {
        return $this->belongsTo(MusicTrack::class, 'track_id');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // Méthodes utiles
    public function getLikesCountAttribute()
    {
        return $this->likes()->count();
    }

    public function getCommentsCountAttribute()
    {
        return $this->comments()->count();
    }

    public function hasLocation()
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    public function getLocationPoint()
    {
        if ($this->hasLocation()) {
            return new Point($this->latitude, $this->longitude);
        }
        return null;
    }

    public function getDistanceFrom($latitude, $longitude)
    {
        if (!$this->hasLocation()) {
            return null;
        }

        $earthRadius = 6371; // Rayon de la Terre en km

        $latDiff = deg2rad($latitude - $this->latitude);
        $lonDiff = deg2rad($longitude - $this->longitude);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($latitude)) *
             sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    // Scopes
    public function scopeWithLocation($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    public function scopePublicLocation($query)
    {
        return $query->where('is_location_public', true);
    }

    public function scopeNearby($query, $latitude, $longitude, $radiusKm = 10)
    {
        return $query->withLocation()
            ->selectRaw("*, 
                (6371 * acos(
                    cos(radians(?)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(latitude))
                )) AS distance", [$latitude, $longitude, $latitude])
            ->having('distance', '<', $radiusKm)
            ->orderBy('distance');
    }

    public function scopeWithinBounds($query, $northLat, $southLat, $eastLng, $westLng)
    {
        return $query->withLocation()
            ->whereBetween('latitude', [$southLat, $northLat])
            ->whereBetween('longitude', [$westLng, $eastLng]);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeWithUser($query)
    {
        return $query->with('user:id,name,avatar');
    }

    public function scopeWithTrack($query)
    {
        return $query->with('track:id,title,artist,duration,cover_image');
    }
}