<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\MediaController;

class MigrateMediaToCloudinary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:migrate-to-cloudinary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrer tous les médias existants vers Cloudinary';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Démarrage de la migration des médias vers Cloudinary...');
        
        try {
            $mediaController = app(MediaController::class);
            $result = $mediaController->migrateToCloudinary();
            
            // Extraire les données de la réponse JSON
            $data = json_decode($result->getContent(), true);
            
            $this->info($data['message']);
            
            if (!empty($data['successes'])) {
                $this->info('Succès:');
                foreach ($data['successes'] as $message) {
                    $this->line(' - ' . $message);
                }
            }
            
            if (!empty($data['errors'])) {
                $this->error('Erreurs:');
                foreach ($data['errors'] as $message) {
                    $this->error(' - ' . $message);
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Une erreur est survenue lors de la migration:');
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
} 