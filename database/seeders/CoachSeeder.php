<?php

namespace Database\Seeders;

use App\Models\Coach;
use Illuminate\Database\Seeder;

class CoachSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Éducateurs U5
        Coach::create([
            'name' => 'Fatima Zahra',
            'role' => 'Éducatrice Principale U5',
            'description' => 'Spécialiste de l\'éveil moteur des tout-petits',
            'category' => 'u5',
        ]);

        Coach::create([
            'name' => 'Karim Raji',
            'role' => 'Animateur Sportif U5',
            'description' => 'Diplômé en petite enfance',
            'category' => 'u5',
        ]);

        // Éducateurs U7
        Coach::create([
            'name' => 'Fouad Majidi',
            'role' => 'Éducateur Principal U7',
            'description' => 'Spécialiste petite enfance',
            'category' => 'u7',
        ]);

        Coach::create([
            'name' => 'Nadia Ziani',
            'role' => 'Éducatrice Adjointe U7',
            'description' => 'Diplômée en psychomotricité',
            'category' => 'u7',
        ]);

        // Éducateurs U9
        Coach::create([
            'name' => 'Hassan Ouaddi',
            'role' => 'Éducateur Principal U9',
            'description' => 'Diplôme fédéral jeunes, ancien joueur',
            'category' => 'u9',
        ]);

        Coach::create([
            'name' => 'Samira Lahlou',
            'role' => 'Éducatrice Adjointe U9',
            'description' => 'Spécialiste développement technique',
            'category' => 'u9',
        ]);

        // Éducateurs U11
        Coach::create([
            'name' => 'Rachid Bennani',
            'role' => 'Éducateur Principal U11',
            'description' => 'Diplôme UEFA C, formateur technique',
            'category' => 'u11',
        ]);

        Coach::create([
            'name' => 'Jamal Alami',
            'role' => 'Préparateur Physique U11',
            'description' => 'Spécialiste coordination motrice',
            'category' => 'u11',
        ]);

        // Éducateurs U13
        Coach::create([
            'name' => 'Khalid Fadil',
            'role' => 'Éducateur Principal U13',
            'description' => 'Diplôme UEFA B, formateur jeunes',
            'category' => 'u13',
        ]);

        Coach::create([
            'name' => 'Samir Tazi',
            'role' => 'Entraîneur Adjoint U13',
            'description' => 'Ancien joueur professionnel',
            'category' => 'u13',
        ]);

        // Éducateurs U15
        Coach::create([
            'name' => 'Marouane Zaki',
            'role' => 'Entraîneur Principal U15',
            'description' => 'Diplôme UEFA B, spécialiste du développement des jeunes',
            'category' => 'u15',
        ]);

        Coach::create([
            'name' => 'Younes Benzarti',
            'role' => 'Préparateur Physique U15',
            'description' => 'Master en sciences du sport',
            'category' => 'u15',
        ]);

        // Éducateurs U17
        Coach::create([
            'name' => 'Ahmed Naciri',
            'role' => 'Entraîneur Principal U17',
            'description' => 'Diplôme UEFA A, ancien joueur professionnel',
            'category' => 'u17',
        ]);

        Coach::create([
            'name' => 'Mohammed Berrada',
            'role' => 'Entraîneur Adjoint U17',
            'description' => 'Certifié en préparation mentale',
            'category' => 'u17',
        ]);

        // Éducateurs U19
        Coach::create([
            'name' => 'Karim Laalej',
            'role' => 'Entraîneur Principal U19',
            'description' => 'Diplôme UEFA Pro, expérience internationale',
            'category' => 'u19',
        ]);

        Coach::create([
            'name' => 'Aziz Benali',
            'role' => 'Analyste Vidéo U19',
            'description' => 'Expert en analyse de performance',
            'category' => 'u19',
        ]);

        // Éducateurs Seniors
        Coach::create([
            'name' => 'Omar Brahim',
            'role' => 'Entraîneur Principal Seniors',
            'description' => 'Diplôme UEFA Pro, ancien sélectionneur national',
            'category' => 'seniors',
        ]);

        Coach::create([
            'name' => 'Mehdi Zouhri',
            'role' => 'Entraîneur Adjoint Seniors',
            'description' => 'Diplôme UEFA A, spécialiste tactique',
            'category' => 'seniors',
        ]);
    }
}
