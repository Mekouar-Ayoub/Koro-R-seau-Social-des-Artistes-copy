<?php
// app/Models/OauthProvider.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OauthProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_token',
        'provider_refresh_token',
    ];

    protected $hidden = [
        'provider_token',
        'provider_refresh_token',
    ];

    protected $casts = [
        'provider_token' => 'encrypted',
        'provider_refresh_token' => 'encrypted',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Méthodes utiles
    public function isExpired()
    {
        // Pour Spotify, on pourrait vérifier l'expiration du token
        // Cette logique dépend du provider
        return false;
    }

    public function refreshToken()
    {
        // Logique pour renouveler le token selon le provider
        switch ($this->provider) {
            case 'spotify':
                return $this->refreshSpotifyToken();
            case 'google':
                return $this->refreshGoogleToken();
            case 'facebook':
                return $this->refreshFacebookToken();
            default:
                return false;
        }
    }

    private function refreshSpotifyToken()
    {
        // TODO: Implémenter le refresh token Spotify
        return false;
    }

    private function refreshGoogleToken()
    {
        // TODO: Implémenter le refresh token Google
        return false;
    }

    private function refreshFacebookToken()
    {
        // TODO: Implémenter le refresh token Facebook
        return false;
    }

    // Scopes
    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }
}