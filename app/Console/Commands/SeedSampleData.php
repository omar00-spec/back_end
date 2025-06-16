<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\Coach;
use App\Models\Player;
use App\Models\Schedule;
use App\Models\MatchModel;
use App\Models\News;

class SeedSampleData extends Command
{
    protected $signature = 'app:seed-sample-data';
    protected $description = 'Seed the database with sample data for testing purposes';

    public function handle()
    {
        $this->info('Seeding sample data...');

        // Création des catégories
        $this->info('Creating categories...');
        $categories = [
            ['name' => 'U5', 'age_min' => 4, 'age_max' => 5, 'description' => 'Découverte ludique du football pour les tout-petits, axée sur la motricité et la socialisation.'],
            ['name' => 'U7', 'age_min' => 6, 'age_max' => 7, 'description' => 'Initiation aux bases techniques dans un cadre ludique et développement des habiletés motrices.'],
            ['name' => 'U9', 'age_min' => 8, 'age_max' => 9, 'description' => 'Apprentissage technique plus approfondi et introduction aux principes collectifs de base.'],
            ['name' => 'U11', 'age_min' => 10, 'age_max' => 11, 'description' => 'Développement technique et tactique de base. Travail sur la coordination, la motricité et l\'intelligence de jeu. Initiation à l\'esprit d\'équipe et à la compétition dans un cadre éducatif et ludique.'],
            ['name' => 'U13', 'age_min' => 12, 'age_max' => 13, 'description' => 'Développement tactique avancé et travail des phases de transition offensive et défensive.'],
            ['name' => 'U15', 'age_min' => 14, 'age_max' => 15, 'description' => 'Formation complète et spécialisation par poste pour les joueurs plus âgés et confirmés.'],
            ['name' => 'U17', 'age_min' => 16, 'age_max' => 17, 'description' => 'Perfectionnement technique et tactique, préparation à la compétition de haut niveau.'],
            ['name' => 'U19', 'age_min' => 18, 'age_max' => 19, 'description' => 'Préparation à la transition vers le football senior et compétition de haut niveau.'],
            ['name' => 'Seniors', 'age_min' => 20, 'age_max' => 35, 'description' => 'Football compétitif et de haut niveau pour les adultes.'],
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['name' => $categoryData['name']],
                $categoryData
            );
        }

        // Récupération des catégories créées
        $allCategories = Category::all();

        // Création des entraîneurs
        $this->info('Creating coaches...');
        $coaches = [
            ['name' => 'Mohamed Benali', 'email' => 'mohamed.b@academiefoot.com', 'phone' => '0600000001', 'diploma' => 'Brevet d\'État 1er degré', 'category_id' => $allCategories->where('name', 'U11')->first()->id],
            ['name' => 'Sarah Dupont', 'email' => 'sarah.d@academiefoot.com', 'phone' => '0600000002', 'diploma' => 'UEFA B', 'category_id' => $allCategories->where('name', 'U9')->first()->id],
            ['name' => 'Ahmed Kadiri', 'email' => 'ahmed.k@academiefoot.com', 'phone' => '0600000003', 'diploma' => 'Diplôme d\'État', 'category_id' => $allCategories->where('name', 'U13')->first()->id],
            ['name' => 'Jean Martin', 'email' => 'jean.m@academiefoot.com', 'phone' => '0600000004', 'diploma' => 'UEFA A', 'category_id' => $allCategories->where('name', 'U15')->first()->id],
            ['name' => 'Karim Alami', 'email' => 'karim.a@academiefoot.com', 'phone' => '0600000005', 'diploma' => 'Brevet d\'État 2e degré', 'category_id' => $allCategories->where('name', 'U17')->first()->id],
            ['name' => 'Sophie Bernard', 'email' => 'sophie.b@academiefoot.com', 'phone' => '0600000006', 'diploma' => 'Initiateur', 'category_id' => $allCategories->where('name', 'U5')->first()->id],
            ['name' => 'Rachid Tazi', 'email' => 'rachid.t@academiefoot.com', 'phone' => '0600000007', 'diploma' => 'Animateur Fédéral', 'category_id' => $allCategories->where('name', 'U7')->first()->id],
            ['name' => 'Pierre Dubois', 'email' => 'pierre.d@academiefoot.com', 'phone' => '0600000008', 'diploma' => 'UEFA Pro', 'category_id' => $allCategories->where('name', 'Seniors')->first()->id],
            ['name' => 'Layla Chaoui', 'email' => 'layla.c@academiefoot.com', 'phone' => '0600000009', 'diploma' => 'Diplôme Fédéral', 'category_id' => $allCategories->where('name', 'U19')->first()->id],
        ];

        foreach ($coaches as $coachData) {
            Coach::updateOrCreate(
                ['email' => $coachData['email']],
                $coachData
            );
        }

        // Création des plannings d'entraînement
        $this->info('Creating schedules...');
        $schedules = [];

        foreach ($allCategories as $category) {
            $schedulesForCategory = [
                [
                    'category_id' => $category->id,
                    'day' => 'Mardi',
                    'start_time' => '17:00:00',
                    'end_time' => '18:30:00',
                    'activity' => 'Entraînement'
                ],
                [
                    'category_id' => $category->id,
                    'day' => 'Jeudi',
                    'start_time' => '17:00:00',
                    'end_time' => '18:30:00',
                    'activity' => 'Entraînement'
                ]
            ];
            $schedules = array_merge($schedules, $schedulesForCategory);
        }

        foreach ($schedules as $scheduleData) {
            Schedule::updateOrCreate(
                [
                    'category_id' => $scheduleData['category_id'],
                    'day' => $scheduleData['day']
                ],
                $scheduleData
            );
        }

        // Création de matchs
        $this->info('Creating matches...');
        $matches = [
            // Matchs passés
            [
                'category_id' => $allCategories->where('name', 'U11')->first()->id,
                'date' => '2024-04-10',
                'opponent' => 'AS Juniors',
                'location' => 'Stade Municipal',
                'result' => 'Victoire 3-1'
            ],
            [
                'category_id' => $allCategories->where('name', 'U13')->first()->id,
                'date' => '2024-04-12',
                'opponent' => 'FC Étoiles',
                'location' => 'Stade Olympique',
                'result' => 'Match nul 2-2'
            ],
            [
                'category_id' => $allCategories->where('name', 'U15')->first()->id,
                'date' => '2024-04-14',
                'opponent' => 'Sporting Club',
                'location' => 'Stade Central',
                'result' => 'Défaite 1-2'
            ],
            // Matchs à venir pour toutes les catégories
        ];

        // Ajouter matchs à venir pour chaque catégorie
        foreach ($allCategories as $category) {
            // Match dans 1 semaine
            $matches[] = [
                'category_id' => $category->id,
                'date' => date('Y-m-d', strtotime('+1 week')),
                'opponent' => 'FC Avenir ' . $category->name,
                'location' => 'Stade Municipal',
                'result' => null
            ];

            // Match dans 2 semaines
            $matches[] = [
                'category_id' => $category->id,
                'date' => date('Y-m-d', strtotime('+2 weeks')),
                'opponent' => 'AS Excellence ' . $category->name,
                'location' => 'Stade Central',
                'result' => null
            ];
        }

        foreach ($matches as $matchData) {
            MatchModel::updateOrCreate(
                [
                    'category_id' => $matchData['category_id'],
                    'date' => $matchData['date'],
                    'opponent' => $matchData['opponent']
                ],
                $matchData
            );
        }

        // Création d'actualités
        $this->info('Creating news...');
        $newsList = [
            [
                'title' => 'Tournoi de fin de saison approche !',
                'content' => 'Nous sommes ravis d\'annoncer notre tournoi annuel de fin de saison qui se tiendra le 15 juin 2024. Toutes les catégories seront représentées et plusieurs clubs de la région participent. Venez nombreux encourager nos jeunes joueurs !',
                'image' => '/images/news/tournoi.jpg',
                'date' => '2024-05-01'
            ],
            [
                'title' => 'Nouvelle session d\'inscription pour la saison 2024/2025',
                'content' => 'Les inscriptions pour la saison 2024/2025 sont maintenant ouvertes ! Nous vous invitons à inscrire vos enfants dès que possible car les places sont limitées. Des sessions d\'évaluation seront organisées fin juin pour les nouveaux joueurs.',
                'image' => '/images/news/inscription.jpg',
                'date' => '2024-05-02'
            ],
            [
                'title' => 'Victoire éclatante de notre équipe U15',
                'content' => 'Félicitations à notre équipe U15 qui a brillamment remporté le match contre l\'équipe de Sporting Club avec un score de 5-0 ! Un jeu collectif remarquable et une performance exceptionnelle de tous les joueurs.',
                'image' => '/images/news/u15-victoire.jpg',
                'date' => '2024-05-03'
            ],
            [
                'title' => 'Stage de perfectionnement pendant les vacances',
                'content' => 'Nous organisons un stage de perfectionnement du 8 au 12 juillet pour les catégories U11 à U15. Au programme : technique individuelle, tactique collective et tournois. Inscriptions auprès des entraîneurs.',
                'image' => '/images/news/stage.jpg',
                'date' => '2024-05-04'
            ]
        ];

        foreach ($newsList as $newsData) {
            News::updateOrCreate(
                [
                    'title' => $newsData['title']
                ],
                $newsData
            );
        }

        $this->info('Sample data seeded successfully!');
        return Command::SUCCESS;
    }
}
