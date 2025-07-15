<?php
// app/Http/Controllers/SocialAuthController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\OauthProvider;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function redirectToProvider($provider)
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
            
            // Chercher un utilisateur existant avec ce provider
            $oauthProvider = OauthProvider::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($oauthProvider) {
                // Utilisateur existant - mise à jour du token
                $user = $oauthProvider->user;
                $oauthProvider->update([
                    'provider_token' => $socialUser->token,
                    'provider_refresh_token' => $socialUser->refreshToken,
                ]);
            } else {
                // Vérifier si un utilisateur existe avec cet email
                $user = User::where('email', $socialUser->getEmail())->first();

                if (!$user) {
                    // Créer un nouvel utilisateur
                    $user = User::create([
                        'name' => $socialUser->getName() ?: $socialUser->getNickname(),
                        'email' => $socialUser->getEmail(),
                        'avatar' => $socialUser->getAvatar(),
                        'password' => null, // Pas de mot de passe pour OAuth
                    ]);

                    // Créer les préférences par défaut
                    UserPreference::create([
                        'user_id' => $user->id,
                        'preferred_genres' => $this->getGenresFromProvider($provider, $socialUser),
                        'auto_location' => true,
                        'public_profile' => true,
                        'email_notifications' => true,
                        'push_notifications' => true,
                    ]);
                }

                // Créer la liaison OAuth
                OauthProvider::create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'provider_token' => $socialUser->token,
                    'provider_refresh_token' => $socialUser->refreshToken,
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // Redirection vers le frontend avec le token
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            return redirect()->to("{$frontendUrl}/auth/callback?token={$token}&provider={$provider}");

        } catch (\Exception $e) {
            Log::error("OAuth callback error for {$provider}: " . $e->getMessage());
            
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            return redirect()->to("{$frontendUrl}/auth/error?message=oauth_failed");
        }
    }

    public function linkProvider(Request $request, $provider)
    {
        $this->validateProvider($provider);
        
        $user = $request->user();

        try {
            $socialUser = Socialite::driver($provider)->user();

            // Vérifier si ce provider n'est pas déjà lié à un autre compte
            $existingProvider = OauthProvider::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->where('user_id', '!=', $user->id)
                ->first();

            if ($existingProvider) {
                return response()->json([
                    'success' => false,
                    'message' => 'This account is already linked to another user'
                ], 400);
            }

            // Créer ou mettre à jour la liaison
            OauthProvider::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => $provider,
                ],
                [
                    'provider_id' => $socialUser->getId(),
                    'provider_token' => $socialUser->token,
                    'provider_refresh_token' => $socialUser->refreshToken,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => ucfirst($provider) . ' account linked successfully'
            ]);

        } catch (\Exception $e) {
            Log::error("OAuth link error for {$provider}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to link account'
            ], 500);
        }
    }

    public function unlinkProvider(Request $request, $provider)
    {
        $this->validateProvider($provider);
        
        $user = $request->user();

        $oauthProvider = OauthProvider::where('user_id', $user->id)
            ->where('provider', $provider)
            ->first();

        if (!$oauthProvider) {
            return response()->json([
                'success' => false,
                'message' => 'Provider not linked to this account'
            ], 404);
        }

        // Vérifier que l'utilisateur a un mot de passe ou un autre provider
        $hasPassword = !is_null($user->password);
        $otherProviders = OauthProvider::where('user_id', $user->id)
            ->where('provider', '!=', $provider)
            ->exists();

        if (!$hasPassword && !$otherProviders) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot unlink the only authentication method. Please set a password first.'
            ], 400);
        }

        $oauthProvider->delete();

        return response()->json([
            'success' => true,
            'message' => ucfirst($provider) . ' account unlinked successfully'
        ]);
    }

    public function getLinkedProviders(Request $request)
    {
        $user = $request->user();
        
        $providers = OauthProvider::where('user_id', $user->id)
            ->select('provider', 'created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'providers' => $providers,
                'has_password' => !is_null($user->password)
            ]
        ]);
    }

    private function validateProvider($provider)
    {
        if (!in_array($provider, ['google', 'facebook', 'spotify'])) {
            abort(404, 'Provider not supported');
        }
    }

    private function getGenresFromProvider($provider, $socialUser)
    {
        // Pour Spotify, on pourrait récupérer les genres préférés
        if ($provider === 'spotify') {
            // TODO: Implémenter la récupération des genres Spotify
            return ['pop', 'rock', 'electronic'];
        }

        return [];
    }
}