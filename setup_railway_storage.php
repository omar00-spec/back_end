<?php

/**
 * Script pour configurer le stockage sur Railway
 * Ce script vérifie et crée les liens symboliques et dossiers nécessaires
 * pour que les médias soient accessibles depuis le frontend Netlify
 */

// Vérifier si le script est exécuté en ligne de commande
if (php_sapi_name() !== 'cli') {
    echo "Ce script doit être exécuté en ligne de commande.";
    exit(1);
}

// Charger les dépendances Laravel
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

echo "Configuration du stockage sur Railway...\n\n";

// 1. Définir les chemins
$publicPath = __DIR__ . '/public';
$storageLinkPath = $publicPath . '/storage';
$storageTargetPath = __DIR__ . '/storage/app/public';

echo "Chemins:\n";
echo "- Public: {$publicPath}\n";
echo "- Lien symbolique: {$storageLinkPath}\n";
echo "- Cible du stockage: {$storageTargetPath}\n\n";

// 2. Vérifier et créer le répertoire de stockage s'il n'existe pas
if (!file_exists($storageTargetPath)) {
    echo "Création du répertoire de stockage: {$storageTargetPath}\n";
    mkdir($storageTargetPath, 0755, true);
    echo "Répertoire créé avec succès.\n";
} else {
    echo "Le répertoire de stockage existe déjà.\n";
}

// 3. Vérifier et créer le lien symbolique si nécessaire
echo "\nVérification du lien symbolique storage...\n";

if (file_exists($storageLinkPath)) {
    if (is_link($storageLinkPath)) {
        echo "Le lien symbolique existe déjà: " . $storageLinkPath . " -> " . readlink($storageLinkPath) . "\n";
        
        // Vérifier si le lien pointe vers le bon répertoire
        if (readlink($storageLinkPath) !== $storageTargetPath) {
            echo "ATTENTION: Le lien symbolique pointe vers un répertoire différent.\n";
            echo "Actuel: " . readlink($storageLinkPath) . "\n";
            echo "Attendu: " . $storageTargetPath . "\n";
            
            // Supprimer et recréer le lien
            echo "Suppression du lien existant...\n";
            unlink($storageLinkPath);
            echo "Création du nouveau lien...\n";
            symlink($storageTargetPath, $storageLinkPath);
            echo "Lien symbolique recréé avec succès.\n";
        }
    } else {
        echo "ERREUR: " . $storageLinkPath . " existe mais n'est pas un lien symbolique.\n";
        echo "Suppression du répertoire/fichier existant...\n";
        
        // Si c'est un répertoire, le supprimer récursivement
        if (is_dir($storageLinkPath)) {
            $files = array_diff(scandir($storageLinkPath), array('.', '..'));
            foreach ($files as $file) {
                if (is_dir($storageLinkPath . '/' . $file)) {
                    // Supprimer récursivement le sous-répertoire
                    system('rm -rf ' . escapeshellarg($storageLinkPath . '/' . $file));
                } else {
                    // Supprimer le fichier
                    unlink($storageLinkPath . '/' . $file);
                }
            }
            rmdir($storageLinkPath);
        } else {
            // Si c'est un fichier, le supprimer simplement
            unlink($storageLinkPath);
        }
        
        echo "Création du lien symbolique...\n";
        symlink($storageTargetPath, $storageLinkPath);
        echo "Lien symbolique créé avec succès.\n";
    }
} else {
    echo "Le lien symbolique n'existe pas. Création...\n";
    symlink($storageTargetPath, $storageLinkPath);
    echo "Lien symbolique créé avec succès.\n";
}

// 4. Créer les sous-répertoires nécessaires dans storage/app/public
$requiredDirs = ['media', 'news', 'documents'];

echo "\nVérification des sous-répertoires nécessaires...\n";
foreach ($requiredDirs as $dir) {
    $dirPath = $storageTargetPath . '/' . $dir;
    if (!file_exists($dirPath)) {
        echo "Création du répertoire: {$dirPath}\n";
        mkdir($dirPath, 0755, true);
        echo "Répertoire {$dir} créé avec succès.\n";
    } else {
        echo "Le répertoire {$dir} existe déjà.\n";
    }
}

// 5. Vérifier les permissions
echo "\nVérification des permissions...\n";
echo "Mise à jour des permissions pour storage/app/public...\n";
system('chmod -R 755 ' . escapeshellarg($storageTargetPath));
echo "Permissions mises à jour avec succès.\n";

// 6. Créer un fichier de test pour vérifier l'accès
$testFilePath = $storageTargetPath . '/media/test-railway.txt';
file_put_contents($testFilePath, 'Ce fichier a été créé pour tester l\'accès au stockage sur Railway. ' . date('Y-m-d H:i:s'));
echo "\nFichier de test créé: {$testFilePath}\n";
echo "Vous pouvez vérifier l'accès à ce fichier via l'URL: https://backend-production-b4aa.up.railway.app/storage/media/test-railway.txt\n";

echo "\nConfiguration du stockage terminée!\n";
echo "Assurez-vous que les fichiers médias sont correctement téléchargés dans le répertoire storage/app/public/media\n";
echo "Exécutez le script standardize_media_paths.php pour standardiser les chemins des fichiers dans la base de données.\n"; 