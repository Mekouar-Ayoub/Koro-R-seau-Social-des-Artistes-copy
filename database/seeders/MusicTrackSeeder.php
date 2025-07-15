<?php
// database/seeders/MusicTrackSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MusicTrack;
use App\Models\User;
use App\Models\MusicGenre;

class MusicTrackSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();
        $genres = MusicGenre::pluck('name')->toArray();

        // Morceaux d'exemple réalistes
        $tracks = [
            [
                'title' => 'Sunset Dreams',
                'artist' => 'Alice Martin',
                'album' => 'Indie Vibes',
                'genre' => 'Indie',
                'duration' => 234, // 3:54
                'description' => 'Un morceau indie mélancolique parfait pour les couchers de soleil',
            ],
            [
                'title' => 'Neon Nights',
                'artist' => 'Bob Johnson',
                'album' => 'Electronic Pulse',
                'genre' => 'Electronic',
                'duration' => 287, // 4:47
                'description' => 'Beat électronique entraînant pour les soirées en ville',
            ],
            [
                'title' => 'Blue Monday Jazz',
                'artist' => 'Sarah Williams',
                'album' => 'Modern Jazz Sessions',
                'genre' => 'Jazz',
                'duration' => 342, // 5:42
                'description' => 'Interprétation jazz moderne du classique de New Order',
            ],
            [
                'title' => 'Thunder Road',
                'artist' => 'Mike Davis',
                'album' => 'Rock Anthems',
                'genre' => 'Rock',
                'duration' => 198, // 3:18
                'description' => 'Guitares puissantes et riffs mémorables',
            ],
            [
                'title' => 'City Lights',
                'artist' => 'Emma Garcia',
                'album' => 'Urban Beats',
                'genre' => 'Hip-Hop',
                'duration' => 189, // 3:09
                'description' => 'Flow urbain sur une prod moderne',
            ],
            [
                'title' => 'Desert Wind',
                'artist' => 'Ahmed Hassan',
                'album' => 'Sahara Sounds',
                'genre' => 'World',
                'duration' => 276, // 4:36
                'description' => 'Fusion de musique traditionnelle marocaine et moderne',
            ],
            [
                'title' => 'Seoul Night',
                'artist' => 'Lisa Chen',
                'album' => 'K-Pop Revolution',
                'genre' => 'Pop',
                'duration' => 203, // 3:23
                'description' => 'Pop entraînante avec des influences asiatiques',
            ],
            [
                'title' => 'Reggae Sunrise',
                'artist' => 'Carlos Rodriguez',
                'album' => 'Island Vibes',
                'genre' => 'Reggae',
                'duration' => 256, // 4:16
                'description' => 'Reggae positif pour commencer la journée',
            ],
            [
                'title' => 'Acoustic Dreams',
                'artist' => 'Alice Martin',
                'album' => 'Unplugged Sessions',
                'genre' => 'Folk',
                'duration' => 187, // 3:07
                'description' => 'Version acoustique intime et émotionnelle',
            ],
            [
                'title' => 'Bass Drop',
                'artist' => 'Bob Johnson',
                'album' => 'Club Bangers',
                'genre' => 'Dubstep',
                'duration' => 312, // 5:12
                'description' => 'Dubstep intense pour les dancefloors',
            ],
            [
                'title' => 'Midnight Blues',
                'artist' => 'Sarah Williams',
                'album' => 'Late Night Sessions',
                'genre' => 'Blues',
                'duration' => 298, // 4:58
                'description' => 'Blues mélancolique pour les nuits d\'insomnie',
            ],
            [
                'title' => 'Metal Storm',
                'artist' => 'Mike Davis',
                'album' => 'Heavy Thunder',
                'genre' => 'Metal',
                'duration' => 245, // 4:05
                'description' => 'Metal brutal avec solos de guitare épiques',
            ],
            [
                'title' => 'R&B Smooth',
                'artist' => 'Emma Garcia',
                'album' => 'Soul Vibes',
                'genre' => 'R&B',
                'duration' => 221, // 3:41
                'description' => 'R&B smooth et sensuel',
            ],
            [
                'title' => 'Berber Chant',
                'artist' => 'Ahmed Hassan',
                'album' => 'Atlas Mountains',
                'genre' => 'World',
                'duration' => 334, // 5:34
                'description' => 'Chant berbère traditionnel revisité',
            ],
            [
                'title' => 'Dance Revolution',
                'artist' => 'Lisa Chen',
                'album' => 'Dance Floor Hits',
                'genre' => 'Pop',
                'duration' => 195, // 3:15
                'description' => 'Pop dance parfaite pour faire la fête',
            ],
        ];

        foreach ($tracks as $trackData) {
            // Trouver l'utilisateur correspondant à l'artiste
            $user = $users->firstWhere('name', $trackData['artist']) ?? $users->random();
            
            $track = MusicTrack::create([
                'user_id' => $user->id,
                'title' => $trackData['title'],
                'artist' => $trackData['artist'],
                'album' => $trackData['album'],
                'genre' => $trackData['genre'],
                'duration' => $trackData['duration'],
                'file_path' => 'storage/music/' . str_replace(' ', '_', strtolower($trackData['title'])) . '.mp3',
                'file_size' => rand(3000000, 8000000), // 3-8 MB
                'description' => $trackData['description'],
                'is_public' => true,
                'play_count' => rand(0, 1000),
            ]);

            // Ajouter des likes aléatoires
            $maxLikes = min($users->count(), 8);
            $likesCount = rand(2, $maxLikes);
            $usersWhoLike = $users->random($likesCount);
            foreach ($usersWhoLike as $userWhoLikes) {
                if (!$track->likes()->where('user_id', $userWhoLikes->id)->exists()) {
                    $track->likes()->create(['user_id' => $userWhoLikes->id]);
                }
            }
        }

        // Ajouter plus de morceaux générés automatiquement
        for ($i = 0; $i < 30; $i++) {
            $user = $users->random();
            $genre = fake()->randomElement($genres);
            
            MusicTrack::create([
                'user_id' => $user->id,
                'title' => fake()->words(rand(1, 3), true),
                'artist' => $user->name,
                'album' => fake()->words(rand(1, 2), true),
                'genre' => $genre,
                'duration' => rand(120, 360), // 2-6 minutes
                'file_path' => 'storage/music/generated_' . ($i + 1) . '.mp3',
                'file_size' => rand(2000000, 10000000), // 2-10 MB
                'description' => fake()->sentence(),
                'is_public' => fake()->boolean(90), // 90% publics
                'play_count' => rand(0, 500),
            ]);
        }

        $this->command->info('Morceaux de musique créés avec succès!');
    }
}