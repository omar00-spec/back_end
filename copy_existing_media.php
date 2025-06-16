<?php
// Ce script copie tous les fichiers médias existants de storage/app/public/media vers public/storage/media

// Définir le chemin de base
$basePath = __DIR__;

// Chemins source et destination
$sourcePath = $basePath . '/storage/app/public/media';
$destinationPath = $basePath . '/public/storage/media';

// Vérifier si le répertoire de destination existe, sinon le créer
if (!file_exists($destinationPath)) {
    mkdir($destinationPath, 0777, true);
    echo "Répertoire destination créé: {$destinationPath}\n";
}

// Fonction pour copier récursivement les fichiers d'un répertoire à un autre
function copyDirectory($source, $destination) {
    if (!is_dir($source)) {
        echo "Erreur: Le répertoire source n'existe pas: {$source}\n";
        return;
    }
    
    echo "Traitement du répertoire: {$source}\n";
    
    $files = scandir($source);
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $sourcePath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;
            
            if (is_dir($sourcePath)) {
                // Créer le sous-répertoire de destination s'il n'existe pas
                if (!file_exists($destPath)) {
                    mkdir($destPath, 0777, true);
                    echo "Sous-répertoire créé: {$destPath}\n";
                }
                
                // Copier récursivement les fichiers du sous-répertoire
                copyDirectory($sourcePath, $destPath);
            } else {
                // Copier le fichier s'il n'existe pas déjà ou s'il est plus récent
                if (!file_exists($destPath) || filemtime($sourcePath) > filemtime($destPath)) {
                    if (copy($sourcePath, $destPath)) {
                        echo "Fichier copié: {$file}\n";
                    } else {
                        echo "Erreur lors de la copie du fichier: {$file}\n";
                    }
                } else {
                    echo "Le fichier existe déjà et est à jour: {$file}\n";
                }
            }
        }
    }
}

// Vérifier que le répertoire source existe
if (file_exists($sourcePath)) {
    echo "Début de la copie des fichiers de {$sourcePath} vers {$destinationPath}\n";
    copyDirectory($sourcePath, $destinationPath);
    echo "Terminé!\n";
} else {
    echo "Erreur: Le répertoire source n'existe pas: {$sourcePath}\n";
    
    // Essayer de trouver des alternatives
    $alternatives = [
        $basePath . '/storage/app/media',
        $basePath . '/storage/media',
        $basePath . '/public/media'
    ];
    
    foreach ($alternatives as $alt) {
        if (file_exists($alt)) {
            echo "Source alternative trouvée: {$alt}\n";
            echo "Début de la copie des fichiers de {$alt} vers {$destinationPath}\n";
            copyDirectory($alt, $destinationPath);
            echo "Terminé!\n";
            break;
        }
    }
}

// Vérifier si des fichiers MediaModel existent dans la base de données et corriger leurs chemins
try {
    require_once __DIR__ . '/vendor/autoload.php';
    
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    $count = 0;
    $medias = \App\Models\Media::all();
    
    echo "Vérification des chemins de {$medias->count()} médias dans la base de données...\n";
    
    foreach ($medias as $media) {
        if (!$media->file_path) continue;
        
        // Si ce n'est pas une URL et que le fichier n'existe pas dans public/storage
        if (!filter_var($media->file_path, FILTER_VALIDATE_URL)) {
            $filename = basename($media->file_path);
            $publicPath = $basePath . '/public/storage/media/' . $filename;
            $storagePath = $basePath . '/storage/app/public/media/' . $filename;
            
            // Si le fichier existe dans storage mais pas dans public
            if (file_exists($storagePath) && !file_exists($publicPath)) {
                if (copy($storagePath, $publicPath)) {
                    echo "Fichier de média ID {$media->id} copié: {$filename}\n";
                    $count++;
                }
            }
        }
    }
    
    echo "{$count} fichiers médias copiés depuis la base de données.\n";
    
} catch (\Exception $e) {
    echo "Erreur lors de l'accès à la base de données: " . $e->getMessage() . "\n";
}

echo "Script terminé!\n"; 