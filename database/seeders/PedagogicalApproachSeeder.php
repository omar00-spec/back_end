<?php

namespace Database\Seeders;

use App\Models\PedagogicalApproach;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PedagogicalApproachSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $approaches = [
            [
                'description' => 'Développement technique progressif adapté à chaque catégorie d\'âge',
                'order' => 1
            ],
            [
                'description' => 'Accent mis sur le plaisir de jouer et l\'esprit d\'équipe',
                'order' => 2
            ],
            [
                'description' => 'Valorisation de l\'intelligence de jeu et de la prise de décision',
                'order' => 3
            ],
            [
                'description' => 'Suivi personnalisé de chaque joueur avec évaluations régulières',
                'order' => 4
            ],
            [
                'description' => 'Intégration des valeurs éducatives et du respect dans l\'apprentissage',
                'order' => 5
            ],
            [
                'description' => 'Méthodes d\'entraînement modernes inspirées des meilleures académies européennes',
                'order' => 6
            ],
        ];

        foreach ($approaches as $approach) {
            PedagogicalApproach::create($approach);
        }
    }
}
