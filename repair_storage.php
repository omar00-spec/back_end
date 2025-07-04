<?php

// Charger l'environnement Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Script de réparation du stockage pour ACOS Football Academy ===\n\n";

// 1. Vérifier si le répertoire storage/app/public existe
echo "1. Vérification des répertoires de stockage...\n";
$storagePublicPath = storage_path('app/public');
$publicStoragePath = public_path('storage');
$mediaPath = storage_path('app/public/media');
$publicMediaPath = public_path('storage/media');

if (!file_exists($storagePublicPath)) {
    echo "   - Création du répertoire {$storagePublicPath}... ";
    mkdir($storagePublicPath, 0755, true);
    echo "OK\n";
} else {
    echo "   - Le répertoire {$storagePublicPath} existe déjà. OK\n";
}

if (!file_exists($mediaPath)) {
    echo "   - Création du répertoire {$mediaPath}... ";
    mkdir($mediaPath, 0755, true);
    echo "OK\n";
} else {
    echo "   - Le répertoire {$mediaPath} existe déjà. OK\n";
}

// 2. Vérifier si le lien symbolique storage existe
echo "\n2. Vérification du lien symbolique...\n";
if (!file_exists($publicStoragePath)) {
    echo "   - Le lien symbolique n'existe pas. Création...\n";
    try {
        symlink($storagePublicPath, $publicStoragePath);
        echo "   - Lien symbolique créé avec succès.\n";
    } catch (Exception $e) {
        echo "   - ERREUR: Impossible de créer le lien symbolique: " . $e->getMessage() . "\n";
        echo "   - Essai de copie des fichiers à la place...\n";
        try {
            if (!file_exists($publicStoragePath)) {
                mkdir($publicStoragePath, 0755, true);
            }
            echo "   - Répertoire public/storage créé.\n";
        } catch (Exception $e) {
            echo "   - ERREUR CRITIQUE: Impossible de créer le répertoire: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "   - Le lien symbolique existe déjà. OK\n";
}

// 3. Vérifier si le répertoire public/storage/media existe
if (!file_exists($publicMediaPath)) {
    echo "   - Création du répertoire {$publicMediaPath}... ";
    mkdir($publicMediaPath, 0755, true);
    echo "OK\n";
} else {
    echo "   - Le répertoire {$publicMediaPath} existe déjà. OK\n";
}

// 4. Corriger les permissions
echo "\n3. Correction des permissions...\n";
try {
    chmod($storagePublicPath, 0755);
    chmod($publicStoragePath, 0755);
    chmod($mediaPath, 0755);
    chmod($publicMediaPath, 0755);
    echo "   - Permissions mises à jour avec succès.\n";
} catch (Exception $e) {
    echo "   - AVERTISSEMENT: Impossible de modifier les permissions: " . $e->getMessage() . "\n";
}

// 5. Synchroniser les fichiers entre storage/app/public/media et public/storage/media
echo "\n4. Synchronisation des fichiers media...\n";
try {
    // Copier les fichiers de storage/app/public/media vers public/storage/media
    $mediaFiles = glob($mediaPath . '/*');
    $count = 0;
    foreach ($mediaFiles as $file) {
        if (is_file($file)) {
            $fileName = basename($file);
            $destination = $publicMediaPath . '/' . $fileName;
            if (!file_exists($destination)) {
                copy($file, $destination);
                $count++;
            }
        }
    }
    echo "   - {$count} fichiers copiés vers public/storage/media.\n";
    
    // Copier les fichiers de public/storage/media vers storage/app/public/media
    $publicMediaFiles = glob($publicMediaPath . '/*');
    $count = 0;
    foreach ($publicMediaFiles as $file) {
        if (is_file($file)) {
            $fileName = basename($file);
            $destination = $mediaPath . '/' . $fileName;
            if (!file_exists($destination)) {
                copy($file, $destination);
                $count++;
            }
        }
    }
    echo "   - {$count} fichiers copiés vers storage/app/public/media.\n";
} catch (Exception $e) {
    echo "   - ERREUR: Problème lors de la synchronisation des fichiers: " . $e->getMessage() . "\n";
}

// 6. Vérification des entrées dans la base de données
echo "\n5. Vérification des entrées dans la base de données...\n";
try {
    $mediaCount = \App\Models\Media::count();
    echo "   - Nombre d'entrées dans la table Media: {$mediaCount}\n";
    
    // Vérifier les chemins incorrects
    $invalidPaths = \App\Models\Media::where('file_path', 'like', '%null%')
        ->orWhereNull('file_path')
        ->get();
    
    if ($invalidPaths->count() > 0) {
        echo "   - ATTENTION: {$invalidPaths->count()} entrées ont des chemins invalides.\n";
        
        foreach ($invalidPaths as $media) {
            echo "     * ID {$media->id}: {$media->file_path} - Tentative de correction...\n";
            
            // Si le media a un titre, essayer de trouver un fichier correspondant
            $possibleFiles = glob($mediaPath . '/*' . $media->title . '*');
            if (!empty($possibleFiles)) {
                $fileName = basename($possibleFiles[0]);
                $newPath = url('storage/media/' . $fileName);
                $media->file_path = $newPath;
                $media->save();
                echo "       Corrigé avec {$newPath}\n";
            } else {
                echo "       Aucun fichier correspondant trouvé.\n";
            }
        }
    } else {
        echo "   - Tous les chemins semblent valides. OK\n";
    }
} catch (Exception $e) {
    echo "   - ERREUR: Problème lors de la vérification de la base de données: " . $e->getMessage() . "\n";
}

// 7. Test final
echo "\n6. Test de création de fichier...\n";
try {
    $testFile = $publicMediaPath . '/test_' . time() . '.txt';
    file_put_contents($testFile, 'Test file creation');
    echo "   - Fichier test créé avec succès: {$testFile}\n";
    
    $testUrl = url('storage/media/' . basename($testFile));
    echo "   - URL du fichier test: {$testUrl}\n";
} catch (Exception $e) {
    echo "   - ERREUR: Impossible de créer un fichier test: " . $e->getMessage() . "\n";
}

echo "\n=== Réparation terminée ===\n";
echo "Si vous rencontrez encore des problèmes, vérifiez les permissions du serveur et\n";
echo "assurez-vous que l'utilisateur du serveur web a les droits d'écriture sur les répertoires de stockage.\n"; 