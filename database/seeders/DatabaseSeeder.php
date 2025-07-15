<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            MusicGenreSeeder::class,
            UserSeeder::class,
            MusicTrackSeeder::class,
            PlaylistSeeder::class,
            MusicPostSeeder::class,
        ]);
        
        $this->command->info('🎵 Base de données Kore peuplée avec succès!');
        $this->command->info('📊 Données créées:');
        $this->command->info('   - Genres musicaux: ' . \App\Models\MusicGenre::count());
        $this->command->info('   - Utilisateurs: ' . \App\Models\User::count());
        $this->command->info('   - Morceaux: ' . \App\Models\MusicTrack::count());
        $this->command->info('   - Playlists: ' . \App\Models\Playlist::count());
        $this->command->info('   - Posts: ' . \App\Models\MusicPost::count());
        $this->command->info('   - Likes: ' . \App\Models\Like::count());
        $this->command->info('   - Commentaires: ' . \App\Models\Comment::count());
    }
}