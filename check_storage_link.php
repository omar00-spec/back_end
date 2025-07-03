<?php

/**
 * Script pour vérifier et créer le lien symbolique de storage si nécessaire
 * À exécuter sur le serveur backend pour s'assurer que les fichiers médias sont accessibles
 */

// Vérifier si le script est exécuté en ligne de commande
if (php_sapi_name() !== 'cli') {
    echo "Ce script doit être exécuté en ligne de commande.";
    exit(1);
}

// Définir les chemins
$publicPath = __DIR__ . '/public';
$storageLinkPath = $publicPath . '/storage';
$storageTargetPath = __DIR__ . '/storage/app/public';

echo "Vérification du lien symbolique storage...\n";

// Vérifier si le lien existe déjà
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
    
    // Vérifier si le répertoire cible existe
    if (!file_exists($storageTargetPath)) {
        echo "Création du répertoire cible: " . $storageTargetPath . "\n";
        mkdir($storageTargetPath, 0755, true);
    }
    
    // Créer le lien symbolique
    symlink($storageTargetPath, $storageLinkPath);
    echo "Lien symbolique créé avec succès.\n";
}

// Vérifier si le répertoire media existe dans storage/app/public
$mediaDir = $storageTargetPath . '/media';
if (!file_exists($mediaDir)) {
    echo "Création du répertoire media dans storage/app/public...\n";
    mkdir($mediaDir, 0755, true);
    echo "Répertoire media créé avec succès.\n";
} else {
    echo "Le répertoire media existe déjà dans storage/app/public.\n";
}

echo "\nVérification des permissions...\n";
echo "Mise à jour des permissions pour storage/app/public...\n";
system('chmod -R 755 ' . escapeshellarg($storageTargetPath));
echo "Permissions mises à jour avec succès.\n";

echo "\nTerminé!\n";
echo "Assurez-vous que le serveur web a les permissions nécessaires pour accéder aux fichiers.\n";
echo "Vous pouvez maintenant télécharger vos fichiers médias dans: " . $storageTargetPath . "/media\n"; 