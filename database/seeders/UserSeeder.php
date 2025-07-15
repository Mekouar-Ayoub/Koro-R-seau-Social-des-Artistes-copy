<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Utilisateur admin/test principal
        $admin = User::firstOrCreate(
            ['email' => 'admin@kore.com'],
            [
                'name' => 'Admin Kore',
                'password' => Hash::make('password123'),
                'bio' => 'Administrateur de la plateforme Kore - Réseau social musical',
                'location' => 'Casablanca, Maroc',
                'is_private' => false,
                'email_verified_at' => now(),
            ]
        );

        // Créer les préférences seulement si elles n'existent pas
        if (!$admin->preferences) {
            UserPreference::create([
                'user_id' => $admin->id,
                'preferred_genres' => ['Pop', 'Rock', 'Electronic', 'Jazz'],
                'auto_location' => true,
                'public_profile' => true,
                'email_notifications' => true,
                'push_notifications' => true,
            ]);
        }

        // Utilisateurs de test
        $users = [
            [
                'name' => 'Alice Martin',
                'email' => 'alice@example.com',
                'bio' => 'Passionnée de musique indie et folk 🎵',
                'location' => 'Paris, France',
                'genres' => ['Indie', 'Folk', 'Alternative'],
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'bio' => 'DJ et producteur de musique électronique',
                'location' => 'Berlin, Allemagne',
                'genres' => ['Electronic', 'House', 'Techno'],
            ],
            [
                'name' => 'Sarah Williams',
                'email' => 'sarah@example.com',
                'bio' => 'Chanteuse et compositrice de jazz moderne',
                'location' => 'New York, USA',
                'genres' => ['Jazz', 'Soul', 'Blues'],
            ],
            [
                'name' => 'Mike Davis',
                'email' => 'mike@example.com',
                'bio' => 'Guitariste rock et amateur de metal',
                'location' => 'Londres, UK',
                'genres' => ['Rock', 'Metal', 'Punk'],
            ],
            [
                'name' => 'Emma Garcia',
                'email' => 'emma@example.com',
                'bio' => 'Productrice de hip-hop et R&B',
                'location' => 'Los Angeles, USA',
                'genres' => ['Hip-Hop', 'R&B', 'Trap'],
            ],
            [
                'name' => 'Ahmed Hassan',
                'email' => 'ahmed@example.com',
                'bio' => 'Musicien traditionnel et world music',
                'location' => 'Marrakech, Maroc',
                'genres' => ['World', 'Folk', 'Traditional'],
            ],
            [
                'name' => 'Lisa Chen',
                'email' => 'lisa@example.com',
                'bio' => 'Fan de K-pop et musique asiatique',
                'location' => 'Seoul, Corée du Sud',
                'genres' => ['Pop', 'K-Pop', 'Electronic'],
            ],
            [
                'name' => 'Carlos Rodriguez',
                'email' => 'carlos@example.com',
                'bio' => 'Bassiste de reggae et musiques latines',
                'location' => 'Barcelona, Espagne',
                'genres' => ['Reggae', 'Latin', 'Funk'],
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password123'),
                    'bio' => $userData['bio'],
                    'location' => $userData['location'],
                    'is_private' => fake()->boolean(20), // 20% de chance d'être privé
                    'email_verified_at' => now(),
                ]
            );

            // Créer les préférences seulement si elles n'existent pas
            if (!$user->preferences) {
                UserPreference::create([
                    'user_id' => $user->id,
                    'preferred_genres' => $userData['genres'],
                    'auto_location' => fake()->boolean(80),
                    'public_profile' => !$user->is_private,
                    'email_notifications' => fake()->boolean(70),
                    'push_notifications' => fake()->boolean(60),
                ]);
            }
        }

        // Créer des relations de suivi aléatoires
        $allUsers = User::all();
        foreach ($allUsers as $user) {
            $otherUsers = $allUsers->where('id', '!=', $user->id);
            $usersToFollow = $otherUsers->random(rand(2, 5));
            
            foreach ($usersToFollow as $userToFollow) {
                if (!$user->isFollowing($userToFollow->id)) {
                    $user->following()->attach($userToFollow->id);
                }
            }
        }

        $this->command->info('Utilisateurs créés avec succès!');
    }
}