<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusicGenre extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'icon',
    ];

    // Relations
    public function tracks()
    {
        return $this->hasMany(MusicTrack::class, 'genre', 'name');
    }

    // Méthodes utiles
    public function getTracksCountAttribute()
    {
        return $this->tracks()->count();
    }

    public function getPopularTracksAttribute()
    {
        return $this->tracks()->popular()->take(10)->get();
    }

    // Scopes
    public function scopePopular($query)
    {
        return $query->withCount('tracks')->orderBy('tracks_count', 'desc');
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', $name);
    }
}