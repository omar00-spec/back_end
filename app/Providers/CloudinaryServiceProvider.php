<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CloudinaryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            // Vérifier si la table settings existe
            if (Schema::hasTable('settings')) {
                // Récupérer les paramètres Cloudinary depuis la base de données
                $settings = DB::table('settings')
                    ->whereIn('key', ['cloudinary_cloud_name', 'cloudinary_key', 'cloudinary_secret', 'cloudinary_url'])
                    ->get()
                    ->keyBy('key')
                    ->map(function ($item) {
                        return $item->value;
                    })
                    ->toArray();
                
                // Si tous les paramètres sont présents, les définir comme variables d'environnement
                if (isset($settings['cloudinary_cloud_name']) && isset($settings['cloudinary_key']) && isset($settings['cloudinary_secret'])) {
                    putenv("CLOUDINARY_CLOUD_NAME={$settings['cloudinary_cloud_name']}");
                    putenv("CLOUDINARY_KEY={$settings['cloudinary_key']}");
                    putenv("CLOUDINARY_SECRET={$settings['cloudinary_secret']}");
                    putenv("CLOUDINARY_URL={$settings['cloudinary_url']}");
                    
                    // Définir également dans le $_ENV pour que config() puisse les récupérer
                    $_ENV['CLOUDINARY_CLOUD_NAME'] = $settings['cloudinary_cloud_name'];
                    $_ENV['CLOUDINARY_KEY'] = $settings['cloudinary_key'];
                    $_ENV['CLOUDINARY_SECRET'] = $settings['cloudinary_secret'];
                    $_ENV['CLOUDINARY_URL'] = $settings['cloudinary_url'];
                    
                    Log::info('Paramètres Cloudinary chargés depuis la base de données');
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors du chargement des paramètres Cloudinary depuis la base de données', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
} 