<?php

/**
 * Script pour standardiser les chemins des fichiers médias dans la base de données
 * Ce script s'assure que tous les chemins des fichiers médias sont au format correct
 * pour être accessibles depuis le frontend Netlify vers le backend Railway
 */

// Charger les dépendances Laravel
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "Début de la standardisation des chemins de fichiers médias...\n";

// Récupérer tous les médias
$medias = Media::all();
$totalMedias = $medias->count();
$updated = 0;
$skipped = 0;
$errors = 0;

echo "Total des médias trouvés: {$totalMedias}\n";

foreach ($medias as $media) {
    try {
        $originalPath = $media->file_path;
        
        // Si c'est une URL YouTube ou autre service externe, ne pas la modifier
        if (filter_var($originalPath, FILTER_VALIDATE_URL) && 
            (strpos($originalPath, 'youtube') !== false || 
             strpos($originalPath, 'youtu.be') !== false || 
             strpos($originalPath, 'vimeo') !== false ||
             strpos($originalPath, 'embed') !== false)) {
            echo "ID {$media->id}: URL externe, ignorée ({$originalPath})\n";
            $skipped++;
            continue;
        }
        
        // Pour les autres URLs complètes (non YouTube/Vimeo), ne pas les modifier
        if (filter_var($originalPath, FILTER_VALIDATE_URL)) {
            echo "ID {$media->id}: URL complète, ignorée ({$originalPath})\n";
            $skipped++;
            continue;
        }
        
        // Récupérer le nom du fichier
        $fileName = basename($originalPath);
        
        // Standardiser le chemin au format 'media/nom_du_fichier.ext'
        $newPath = 'media/' . $fileName;
        
        // Si le chemin est déjà au bon format, ne pas le modifier
        if ($originalPath === $newPath) {
            echo "ID {$media->id}: Déjà au bon format ({$originalPath})\n";
            $skipped++;
            continue;
        }
        
        // Mettre à jour le chemin
        $media->file_path = $newPath;
        $media->save();
        
        echo "ID {$media->id}: Mis à jour de '{$originalPath}' à '{$newPath}'\n";
        $updated++;
        
    } catch (\Exception $e) {
        echo "ERREUR avec ID {$media->id}: " . $e->getMessage() . "\n";
        Log::error("Erreur lors de la standardisation du chemin pour le média ID {$media->id}: " . $e->getMessage());
        $errors++;
    }
}

echo "\nRésumé:\n";
echo "Total des médias: {$totalMedias}\n";
echo "Médias mis à jour: {$updated}\n";
echo "Médias ignorés: {$skipped}\n";
echo "Erreurs: {$errors}\n";

echo "\nTerminé!\n"; 