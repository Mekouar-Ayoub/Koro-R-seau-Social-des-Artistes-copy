<?php

namespace App\Http\Controllers;

use App\Models\MusicPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class LocationController extends Controller
{
    public function nearbyPosts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'sometimes|numeric|min:0.1|max:100', // en km
            'genre' => 'sometimes|string|exists:music_genres,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->get('radius', 10);

        $query = MusicPost::nearby($latitude, $longitude, $radius)
                         ->publicLocation()
                         ->with([
                             'user:id,name,avatar',
                             'track:id,title,artist,duration,cover_image,genre'
                         ]);

        // Filtre par genre si spécifié
        if ($request->has('genre')) {
            $query->whereHas('track', function($q) use ($request) {
                $q->where('genre', $request->genre);
            });
        }

        $posts = $query->recent()
                      ->paginate($request->get('per_page', 20));

        // Ajouter la distance et des infos supplémentaires
        $posts->getCollection()->transform(function ($post) use ($latitude, $longitude, $request) {
            $post->distance_km = round($post->distance, 2);
            $post->distance_formatted = $this->formatDistance($post->distance);

            if ($request->user()) {
                $post->is_liked = $post->likes()->where('user_id', $request->user()->id)->exists();
                $post->likes_count = $post->likes()->count();
                $post->comments_count = $post->comments()->count();
            }

            unset($post->distance);
            return $post;
        });

        return response()->json([
            'success' => true,
            'data' => $posts,
            'search_info' => [
                'center' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ],
                'radius_km' => $radius,
                'total_found' => $posts->total()
            ]
        ]);
    }

    public function geocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string|max:255',
            'country' => 'sometimes|string|size:2', // Code pays ISO 2 lettres
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $address = $request->address;
        $country = $request->get('country');

        $cacheKey = 'geocode_' . md5($address . $country);

        if ($cachedResult = Cache::get($cacheKey)) {
            return response()->json([
                'success' => true,
                'data' => $cachedResult,
                'cached' => true
            ]);
        }

        try {
            $url = 'https://nominatim.openstreetmap.org/search';
            $params = [
                'q' => $address,
                'format' => 'json',
                'limit' => 5,
                'addressdetails' => 1,
                'extratags' => 1,
            ];

            if ($country) {
                $params['countrycodes'] = $country;
            }

            $response = Http::withHeaders([
                'User-Agent' => 'Kore Music App/1.0'
            ])->get($url, $params);

            if (!$response->successful()) {
                throw new \Exception('Service de géocodage indisponible');
            }

            $results = $response->json();

            if (empty($results)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun résultat trouvé pour cette adresse'
                ], 404);
            }

            $formattedResults = collect($results)->map(function ($result) {
                return [
                    'latitude' => (float) $result['lat'],
                    'longitude' => (float) $result['lon'],
                    'display_name' => $result['display_name'],
                    'type' => $result['type'] ?? 'unknown',
                    'importance' => $result['importance'] ?? 0,
                    'address' => [
                        'house_number' => $result['address']['house_number'] ?? null,
                        'road' => $result['address']['road'] ?? null,
                        'city' => $result['address']['city'] ?? $result['address']['town'] ?? $result['address']['village'] ?? null,
                        'state' => $result['address']['state'] ?? null,
                        'country' => $result['address']['country'] ?? null,
                        'postcode' => $result['address']['postcode'] ?? null,
                    ]
                ];
            })->toArray();

            // Stocker dans le cache
            Cache::put($cacheKey, $formattedResults, now()->addHours(6));

            return response()->json([
                'success' => true,
                'data' => $formattedResults,
                'cached' => false
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du géocodage: ' . $e->getMessage()
            ], 500);
        }
    }

    private function formatDistance($distanceInKm)
    {
        if ($distanceInKm < 1) {
            return round($distanceInKm * 1000) . ' m';
        }
        return round($distanceInKm, 1) . ' km';
    }
}
