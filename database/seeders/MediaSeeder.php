<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Media;

class MediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vider la table avant d'ajouter les données
        Media::truncate();

        // Données exemples pour les photos
        $photos = [
            [
                'category_id' => 1, // Remplacer par l'ID de catégorie approprié
                'type' => 'photo',
                'title' => 'Entraînement U7',
                'file_path' => '/images/gallery-1.jpg',
            ],
            [
                'category_id' => 2,
                'type' => 'photo',
                'title' => 'Match U13',
                'file_path' => '/images/gallery-2.jpg',
            ],
            [
                'category_id' => 3,
                'type' => 'photo',
                'title' => 'Tournoi U11',
                'file_path' => '/images/gallery-3.jpg',
            ],
            [
                'category_id' => 1,
                'type' => 'photo',
                'title' => 'Séance technique U15',
                'file_path' => '/images/gallery-4.jpg',
            ],
            [
                'category_id' => 4,
                'type' => 'photo',
                'title' => 'Équipe U9',
                'file_path' => '/images/gallery-5.jpg',
            ],
            [
                'category_id' => 5,
                'type' => 'photo',
                'title' => 'Festivité fin d\'année',
                'file_path' => '/images/gallery-6.jpg',
            ],
        ];

        // Données exemples pour les vidéos
        $videos = [
            [
                'category_id' => 1,
                'type' => 'video',
                'title' => 'Résumé du tournoi U11',
                'file_path' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            ],
            [
                'category_id' => 2,
                'type' => 'video',
                'title' => 'Exercices techniques U13',
                'file_path' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            ],
            [
                'category_id' => 3,
                'type' => 'video',
                'title' => 'Interview du Directeur Technique',
                'file_path' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            ],
            [
                'category_id' => 4,
                'type' => 'video',
                'title' => 'Meilleurs moments de la saison',
                'file_path' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            ],
        ];

        // Insérer les données
        foreach ($photos as $photo) {
            Media::create($photo);
        }

        foreach ($videos as $video) {
            Media::create($video);
        }
    }
}
