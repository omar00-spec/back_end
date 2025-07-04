<?php

// Chargement de l'environnement Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Vérifier les valeurs des variables d'environnement Cloudinary
echo "=== Configuration Cloudinary ===\n";
echo "CLOUDINARY_URL: " . env('CLOUDINARY_URL') . "\n";
echo "CLOUDINARY_CLOUD_NAME: " . env('CLOUDINARY_CLOUD_NAME') . "\n";
echo "CLOUDINARY_KEY: " . (env('CLOUDINARY_KEY') ? substr(env('CLOUDINARY_KEY'), 0, 5) . '...' : 'Non défini') . "\n";
echo "CLOUDINARY_SECRET: " . (env('CLOUDINARY_SECRET') ? 'Défini (masqué)' : 'Non défini') . "\n";

// Essayer d'utiliser l'instance Cloudinary
try {
    $cloudinary = app('cloudinary');
    echo "Instance Cloudinary créée avec succès.\n";
    
    // Essayer de récupérer la configuration
    $config = $cloudinary->getConfig();
    echo "Configuration récupérée avec succès.\n";
    
    // Essayer d'uploader une image test
    echo "\n=== Test d'upload ===\n";
    // Créer une image test temporaire
    $tempFile = tempnam(sys_get_temp_dir(), 'cloudinary_test_');
    $image = imagecreatetruecolor(100, 100);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    imagepng($image, $tempFile);
    
    echo "Fichier temporaire créé: $tempFile\n";
    
    // Upload sur Cloudinary
    try {
        $result = cloudinary()->upload($tempFile, [
            'folder' => 'tests',
            'public_id' => 'test_' . time(),
            'resource_type' => 'image'
        ]);
        
        echo "Upload réussi!\n";
        echo "URL de l'image: " . $result->getSecurePath() . "\n";
        
        // Supprimer l'image de test
        $publicId = $result->getPublicId();
        echo "Public ID: $publicId\n";
        
        $deleteResult = cloudinary()->destroy($publicId);
        echo "Suppression: " . ($deleteResult ? 'Réussie' : 'Échouée') . "\n";
    } catch (\Exception $e) {
        echo "Erreur lors de l'upload: " . $e->getMessage() . "\n";
    }
    
    // Nettoyer
    unlink($tempFile);
    
} catch (\Exception $e) {
    echo "Erreur lors de l'initialisation de Cloudinary: " . $e->getMessage() . "\n";
}

echo "\nTest terminé.\n"; 