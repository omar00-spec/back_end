<?php

/**
 * Script pour standardiser les chemins des fichiers médias dans la base de données
 * Ce script s'assure que tous les chemins des fichiers médias sont au format correct
 * pour être accessibles depuis le frontend Netlify vers le backend Railway
 */

// Charger l'environnement Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

// Afficher les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Début de la standardisation des chemins de fichiers médias...\n";

// Vérifier si le répertoire de stockage existe
$mediaDir = public_path('storage/media');
if (!file_exists($mediaDir)) {
    echo "Création du répertoire {$mediaDir}\n";
    mkdir($mediaDir, 0755, true);
}

// Récupérer tous les médias
$media = Media::all();
$count = $media->count();
echo "Traitement de {$count} fichiers médias...\n";

$updated = 0;
$errors = 0;
$skipped = 0;

foreach ($media as $item) {
    echo "Traitement de l'ID {$item->id}: {$item->title}\n";
    
    // Ignorer les URLs externes (YouTube, etc.)
    if (filter_var($item->file_path, FILTER_VALIDATE_URL) && 
        (strpos($item->file_path, 'youtube') !== false || 
         strpos($item->file_path, 'youtu.be') !== false || 
         strpos($item->file_path, 'vimeo') !== false ||
         strpos($item->file_path, 'embed') !== false)) {
        echo "  - URL externe, ignorée: {$item->file_path}\n";
        $skipped++;
        continue;
    }
    
    // Si c'est une URL complète mais pas YouTube/Vimeo
    if (filter_var($item->file_path, FILTER_VALIDATE_URL)) {
        // Extraire le nom du fichier
        $fileName = basename(parse_url($item->file_path, PHP_URL_PATH));
        
        // Vérifier si le fichier existe déjà dans storage/media
        $targetPath = public_path("storage/media/{$fileName}");
        if (file_exists($targetPath)) {
            echo "  - Le fichier existe déjà dans storage/media: {$fileName}\n";
        } else {
            // Essayer de télécharger le fichier
            try {
                echo "  - Téléchargement du fichier depuis: {$item->file_path}\n";
                $fileContent = @file_get_contents($item->file_path);
                if ($fileContent !== false) {
                    // Créer le répertoire si nécessaire
                    if (!file_exists(dirname($targetPath))) {
                        mkdir(dirname($targetPath), 0755, true);
                    }
                    
                    // Enregistrer le fichier
                    file_put_contents($targetPath, $fileContent);
                    echo "  - Fichier téléchargé avec succès: {$fileName}\n";
                    
                    // Mettre à jour le chemin dans la base de données
                    $item->file_path = $fileName;
                    $item->save();
                    $updated++;
                    echo "  - Chemin mis à jour dans la base de données: {$fileName}\n";
                } else {
                    echo "  - ERREUR: Impossible de télécharger le fichier: {$item->file_path}\n";
                    $errors++;
                }
            } catch (\Exception $e) {
                echo "  - ERREUR: {$e->getMessage()}\n";
                $errors++;
            }
        }
        continue;
    }
    
    // Pour les chemins relatifs, s'assurer qu'ils sont standardisés
    $fileName = basename($item->file_path);
    
    // Vérifier si le fichier existe
    $targetPath = public_path("storage/media/{$fileName}");
    if (file_exists($targetPath)) {
        // Mettre à jour le chemin dans la base de données si nécessaire
        if ($item->file_path !== $fileName) {
            $item->file_path = $fileName;
            $item->save();
            $updated++;
            echo "  - Chemin standardisé dans la base de données: {$fileName}\n";
        } else {
            echo "  - Le chemin est déjà standardisé: {$fileName}\n";
            $skipped++;
        }
    } else {
        // Le fichier n'existe pas dans storage/media
        echo "  - ATTENTION: Le fichier n'existe pas: {$targetPath}\n";
        
        // Chercher le fichier dans d'autres emplacements possibles
        $possiblePaths = [
            storage_path("app/public/media/{$fileName}"),
            public_path("storage/{$item->file_path}"),
            storage_path("app/public/{$item->file_path}")
        ];
        
        $found = false;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                echo "  - Fichier trouvé à: {$path}\n";
                
                // Copier le fichier vers l'emplacement standard
                if (!file_exists(dirname($targetPath))) {
                    mkdir(dirname($targetPath), 0755, true);
                }
                
                copy($path, $targetPath);
                echo "  - Fichier copié vers: {$targetPath}\n";
                
                // Mettre à jour le chemin dans la base de données
                $item->file_path = $fileName;
                $item->save();
                $updated++;
                echo "  - Chemin mis à jour dans la base de données: {$fileName}\n";
                
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo "  - ERREUR: Impossible de trouver le fichier dans aucun emplacement\n";
            $errors++;
        }
    }
}

echo "\nRésumé:\n";
echo "- Fichiers traités: {$count}\n";
echo "- Chemins mis à jour: {$updated}\n";
echo "- Fichiers ignorés: {$skipped}\n";
echo "- Erreurs: {$errors}\n";
echo "\nTerminé!\n"; 