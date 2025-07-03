<?php

/**
 * Script pour vérifier et réparer les images des actualités
 * Ce script s'assure que toutes les images référencées dans la base de données existent
 * à la fois dans storage/app/public/news et public/storage/news
 */

require __DIR__ . '/vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialiser l'application Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\News;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

echo "Vérification et réparation des images des actualités...\n";

// Vérifier que les répertoires existent
$storageNewsPath = storage_path('app/public/news');
$publicNewsPath = public_path('storage/news');

if (!file_exists($storageNewsPath)) {
    echo "Création du répertoire storage/app/public/news\n";
    mkdir($storageNewsPath, 0755, true);
}

if (!file_exists($publicNewsPath)) {
    echo "Création du répertoire public/storage/news\n";
    mkdir($publicNewsPath, 0755, true);
}

// Récupérer toutes les actualités avec des images
$news = News::whereNotNull('image')->get();
echo "Nombre d'actualités avec des images: " . $news->count() . "\n";

$fixed = 0;
$errors = 0;

foreach ($news as $item) {
    // Ignorer les URLs externes
    if (filter_var($item->image, FILTER_VALIDATE_URL)) {
        echo "Actualité #{$item->id}: Image externe ({$item->image}), ignorée\n";
        continue;
    }
    
    // Obtenir le nom du fichier
    $filename = basename($item->image);
    $storagePath = $storageNewsPath . '/' . $filename;
    $publicPath = $publicNewsPath . '/' . $filename;
    
    echo "Vérification de l'actualité #{$item->id}: {$filename}\n";
    
    // Vérifier si l'image existe dans storage/app/public/news
    $storageExists = file_exists($storagePath);
    
    // Vérifier si l'image existe dans public/storage/news
    $publicExists = file_exists($publicPath);
    
    if ($storageExists && $publicExists) {
        echo "  ✓ L'image existe aux deux emplacements\n";
        continue;
    }
    
    // Si l'image n'existe pas dans storage/app/public/news mais existe dans public/storage/news
    if (!$storageExists && $publicExists) {
        echo "  ! L'image existe uniquement dans public/storage/news, copie vers storage/app/public/news\n";
        try {
            copy($publicPath, $storagePath);
            echo "  ✓ Image copiée avec succès\n";
            $fixed++;
        } catch (Exception $e) {
            echo "  ✗ Erreur lors de la copie: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    // Si l'image n'existe pas dans public/storage/news mais existe dans storage/app/public/news
    if ($storageExists && !$publicExists) {
        echo "  ! L'image existe uniquement dans storage/app/public/news, copie vers public/storage/news\n";
        try {
            copy($storagePath, $publicPath);
            echo "  ✓ Image copiée avec succès\n";
            $fixed++;
        } catch (Exception $e) {
            echo "  ✗ Erreur lors de la copie: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    // Si l'image n'existe nulle part, essayer de la trouver ailleurs
    if (!$storageExists && !$publicExists) {
        echo "  ! L'image n'existe à aucun emplacement\n";
        
        // Chercher dans d'autres répertoires
        $found = false;
        
        // Recherche dans public/storage
        $publicStorageFiles = File::glob(public_path('storage/*.*'));
        foreach ($publicStorageFiles as $file) {
            if (basename($file) === $filename) {
                echo "  ! Image trouvée dans public/storage, copie vers les deux emplacements\n";
                try {
                    copy($file, $storagePath);
                    copy($file, $publicPath);
                    echo "  ✓ Image copiée avec succès\n";
                    $found = true;
                    $fixed++;
                    break;
                } catch (Exception $e) {
                    echo "  ✗ Erreur lors de la copie: " . $e->getMessage() . "\n";
                    $errors++;
                }
            }
        }
        
        // Si toujours pas trouvée, recherche dans storage/app/public
        if (!$found) {
            $storageAppPublicFiles = File::glob(storage_path('app/public/*.*'));
            foreach ($storageAppPublicFiles as $file) {
                if (basename($file) === $filename) {
                    echo "  ! Image trouvée dans storage/app/public, copie vers les deux emplacements\n";
                    try {
                        copy($file, $storagePath);
                        copy($file, $publicPath);
                        echo "  ✓ Image copiée avec succès\n";
                        $found = true;
                        $fixed++;
                        break;
                    } catch (Exception $e) {
                        echo "  ✗ Erreur lors de la copie: " . $e->getMessage() . "\n";
                        $errors++;
                    }
                }
            }
        }
        
        if (!$found) {
            echo "  ✗ Image introuvable dans le système\n";
            $errors++;
        }
    }
}

echo "\nRésumé:\n";
echo "- Total d'actualités vérifiées: " . $news->count() . "\n";
echo "- Images réparées: $fixed\n";
echo "- Erreurs: $errors\n";

echo "\nTerminé!\n"; 