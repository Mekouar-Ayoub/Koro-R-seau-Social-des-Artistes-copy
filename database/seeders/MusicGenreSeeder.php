<?php
// database/seeders/MusicGenreSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MusicGenre;

class MusicGenreSeeder extends Seeder
{
    public function run()
    {
        $genres = [
            ['name' => 'Pop', 'color' => '#FF6B6B', 'icon' => 'music'],
            ['name' => 'Rock', 'color' => '#4ECDC4', 'icon' => 'guitar'],
            ['name' => 'Hip-Hop', 'color' => '#45B7D1', 'icon' => 'microphone'],
            ['name' => 'Jazz', 'color' => '#96CEB4', 'icon' => 'saxophone'],
            ['name' => 'Classical', 'color' => '#FFEAA7', 'icon' => 'piano'],
            ['name' => 'Electronic', 'color' => '#DDA0DD', 'icon' => 'headphones'],
            ['name' => 'R&B', 'color' => '#F39C12', 'icon' => 'music'],
            ['name' => 'Country', 'color' => '#8B4513', 'icon' => 'banjo'],
            ['name' => 'Reggae', 'color' => '#228B22', 'icon' => 'music'],
            ['name' => 'Blues', 'color' => '#191970', 'icon' => 'guitar'],
            ['name' => 'Folk', 'color' => '#D2691E', 'icon' => 'guitar'],
            ['name' => 'Punk', 'color' => '#DC143C', 'icon' => 'guitar'],
            ['name' => 'Metal', 'color' => '#2F4F4F', 'icon' => 'guitar'],
            ['name' => 'Indie', 'color' => '#FF69B4', 'icon' => 'music'],
            ['name' => 'Alternative', 'color' => '#9370DB', 'icon' => 'music'],
            ['name' => 'Funk', 'color' => '#FF4500', 'icon' => 'music'],
            ['name' => 'Soul', 'color' => '#8B008B', 'icon' => 'music'],
            ['name' => 'Disco', 'color' => '#FFD700', 'icon' => 'music'],
            ['name' => 'House', 'color' => '#00CED1', 'icon' => 'headphones'],
            ['name' => 'Techno', 'color' => '#FF1493', 'icon' => 'headphones'],
            ['name' => 'Ambient', 'color' => '#87CEEB', 'icon' => 'headphones'],
            ['name' => 'Trap', 'color' => '#FF6347', 'icon' => 'microphone'],
            ['name' => 'Dubstep', 'color' => '#9932CC', 'icon' => 'headphones'],
            ['name' => 'Trance', 'color' => '#00BFFF', 'icon' => 'headphones'],
            ['name' => 'World', 'color' => '#228B22', 'icon' => 'globe'],
        ];

        foreach ($genres as $genre) {
            MusicGenre::firstOrCreate(
                ['name' => $genre['name']], // Condition de recherche
                $genre // Données à créer si n'existe pas
            );
        }
    }
}