<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListeningHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'track_id',
        'listened_at',
        'duration_listened',
        'completed',
    ];

    protected $casts = [
        'listened_at' => 'datetime',
        'duration_listened' => 'integer',
        'completed' => 'boolean',
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
}