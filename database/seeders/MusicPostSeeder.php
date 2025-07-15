<?php
// database/seeders/MusicPostSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MusicPost;
use App\Models\User;
use App\Models\MusicTrack;

class MusicPostSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();
        $tracks = MusicTrack::all();

        // Coordonnées de quelques villes pour la géolocalisation
        $locations = [
            ['name' => 'Casablanca, Maroc', 'lat' => 33.5731, 'lng' => -7.5898],
            ['name' => 'Paris, France', 'lat' => 48.8566, 'lng' => 2.3522],
            ['name' => 'Berlin, Allemagne', 'lat' => 52.5200, 'lng' => 13.4050],
            ['name' => 'New York, USA', 'lat' => 40.7128, 'lng' => -74.0060],
            ['name' => 'Londres, UK', 'lat' => 51.5074, 'lng' => -0.1278],
            ['name' => 'Los Angeles, USA', 'lat' => 34.0522, 'lng' => -118.2437],
            ['name' => 'Marrakech, Maroc', 'lat' => 31.6295, 'lng' => -7.9811],
            ['name' => 'Barcelona, Espagne', 'lat' => 41.3851, 'lng' => 2.1734],
            ['name' => 'Seoul, Corée du Sud', 'lat' => 37.5665, 'lng' => 126.9780],
        ];

        $postContents = [
            "Nouveau morceau en écoute ! Qu'est-ce que vous en pensez ? 🎵",
            "En studio aujourd'hui, voici le résultat de ma session !",
            "Ce beat me donne des frissons à chaque fois... 🔥",
            "Parfait pour une soirée chill entre amis 🌙",
            "Musique du moment, impossible de m'arrêter de l'écouter !",
            "Inspiration venue à 3h du matin, voilà ce que ça donne 🌃",
            "Collaboration incroyable, merci aux musiciens !",
            "Retour aux sources avec ce morceau authentique ✨",
            "Energy positive pour bien commencer la journée ! ☀️",
            "Mélancolie de fin de soirée... vous ressentez ? 💫",
            "Premier essai dans ce genre, vos retours sont les bienvenus !",
            "Cover de mon morceau préféré, version personnalisée 🎤",
            "Instrumental relaxant pour méditer 🧘‍♀️",
            "Beat parfait pour courir ou faire du sport ! 🏃‍♂️",
            "Nostalgie des années 90, ça vous rappelle quelque chose ?",
        ];

        // Créer 50 posts musicaux
        for ($i = 0; $i < 50; $i++) {
            $user = $users->random();
            $track = $tracks->random();
            $location = fake()->boolean(70) ? fake()->randomElement($locations) : null;
            $hasLocation = !is_null($location);

            MusicPost::create([
                'user_id' => $user->id,
                'track_id' => $track->id,
                'content' => fake()->randomElement($postContents),
                'latitude' => $hasLocation ? $location['lat'] + fake()->randomFloat(4, -0.1, 0.1) : null,
                'longitude' => $hasLocation ? $location['lng'] + fake()->randomFloat(4, -0.1, 0.1) : null,
                'location_name' => $hasLocation ? $location['name'] : null,
                'location_type' => $hasLocation ? fake()->randomElement(['automatic', 'manual']) : null,
                'is_location_public' => $hasLocation ? fake()->boolean(80) : true,
                'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
            ]);
        }

        // Ajouter des likes et commentaires aux posts
        $posts = MusicPost::all();
        foreach ($posts as $post) {
            // Likes aléatoires - s'assurer qu'on ne demande pas plus que disponible
            $maxLikes = min($users->count(), 15);
            $likesCount = rand(1, $maxLikes);
            $usersWhoLike = $users->random($likesCount);
            
            foreach ($usersWhoLike as $userWhoLikes) {
                if (!$post->likes()->where('user_id', $userWhoLikes->id)->exists()) {
                    $post->likes()->create(['user_id' => $userWhoLikes->id]);
                }
            }

            // Commentaires aléatoires
            $commentsCount = rand(0, min(8, $users->count()));
            for ($j = 0; $j < $commentsCount; $j++) {
                $commenter = $users->random();
                $commentContents = [
                    "Super morceau ! 🔥",
                    "J'adore cette vibe !",
                    "Ça me rappelle de bons souvenirs",
                    "Excellent travail, bravo !",
                    "Tu as du talent, continue comme ça !",
                    "Parfait pour ma playlist !",
                    "Cette mélodie est magnifique",
                    "Beat incroyable, félicitations !",
                    "Ça donne envie de danser 💃",
                    "Très belle découverte, merci !",
                    "Production au top 👌",
                    "Atmosphère parfaite",
                ];

                // Éviter les doublons de commentaires du même utilisateur
                if (!$post->comments()->where('user_id', $commenter->id)->exists()) {
                    $post->comments()->create([
                        'user_id' => $commenter->id,
                        'content' => fake()->randomElement($commentContents),
                        'created_at' => fake()->dateTimeBetween($post->created_at, 'now'),
                    ]);
                }
            }
        }

        $this->command->info('Posts musicaux créés avec succès!');
    }
}