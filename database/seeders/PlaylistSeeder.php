<?php
// database/seeders/PlaylistSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Playlist;
use App\Models\User;
use App\Models\MusicTrack;

class PlaylistSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();
        $tracks = MusicTrack::all();

        $playlistsData = [
            [
                'name' => 'Mes favoris 2024',
                'description' => 'Compilation de mes morceaux préférés de cette année',
                'is_public' => true,
                'is_collaborative' => false,
            ],
            [
                'name' => 'Chill Vibes',
                'description' => 'Musique relaxante pour se détendre',
                'is_public' => true,
                'is_collaborative' => false,
            ],
            [
                'name' => 'Workout Mix',
                'description' => 'Énergie maximale pour le sport!',
                'is_public' => true,
                'is_collaborative' => false,
            ],
            [
                'name' => 'Road Trip',
                'description' => 'Les meilleurs morceaux pour un voyage en voiture',
                'is_public' => true,
                'is_collaborative' => true,
            ],
            [
                'name' => 'Indie Discoveries',
                'description' => 'Nouvelles découvertes indie et alternative',
                'is_public' => true,
                'is_collaborative' => true,
            ],
            [
                'name' => 'Jazz Night',
                'description' => 'Collection de jazz pour les soirées cosy',
                'is_public' => true,
                'is_collaborative' => false,
            ],
            [
                'name' => 'Electronic Beats',
                'description' => 'Les meilleurs beats électroniques',
                'is_public' => true,
                'is_collaborative' => true,
            ],
            [
                'name' => 'Playlist Privée',
                'description' => 'Ma collection personnelle secrète',
                'is_public' => false,
                'is_collaborative' => false,
            ],
        ];

        foreach ($playlistsData as $playlistData) {
            $user = $users->random();
            
            $playlist = Playlist::create([
                'user_id' => $user->id,
                'name' => $playlistData['name'],
                'description' => $playlistData['description'],
                'is_public' => $playlistData['is_public'],
                'is_collaborative' => $playlistData['is_collaborative'],
            ]);

            // Ajouter des morceaux aléatoirement
            $tracksToAdd = $tracks->random(rand(5, 15));
            $position = 1;
            
            foreach ($tracksToAdd as $track) {
                $playlist->playlistTracks()->create([
                    'track_id' => $track->id,
                    'position' => $position++,
                    'added_by' => $user->id,
                ]);
            }

            // Ajouter des collaborateurs pour les playlists collaboratives
            if ($playlist->is_collaborative && $users->count() > 1) {
                $maxCollaborators = min($users->where('id', '!=', $user->id)->count(), 3);
                if ($maxCollaborators > 0) {
                    $collaboratorsCount = rand(1, $maxCollaborators);
                    $collaborators = $users->where('id', '!=', $user->id)->random($collaboratorsCount);
                    foreach ($collaborators as $collaborator) {
                        $playlist->collaborators()->attach($collaborator->id, [
                            'role' => 'collaborator',
                            'accepted_at' => now(),
                        ]);
                    }
                }
            }

            // Ajouter des likes
            $maxLikes = min($users->count(), 10);
            $likesCount = rand(3, $maxLikes);
            $usersWhoLike = $users->random($likesCount);
            foreach ($usersWhoLike as $userWhoLikes) {
                if (!$playlist->likes()->where('user_id', $userWhoLikes->id)->exists()) {
                    $playlist->likes()->create(['user_id' => $userWhoLikes->id]);
                }
            }
        }

        $this->command->info('Playlists créées avec succès!');
    }
}
