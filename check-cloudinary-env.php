<?php

// Charger l'environnement Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Vérification des variables d'environnement Cloudinary ===\n\n";

// Variables d'environnement
$variables = [
    'CLOUDINARY_CLOUD_NAME',
    'CLOUDINARY_KEY',
    'CLOUDINARY_SECRET',
    'CLOUDINARY_URL'
];

$allSet = true;

foreach ($variables as $var) {
    $value = env($var);
    $isSet = !empty($value);
    $allSet = $allSet && $isSet;
    
    echo "$var: " . ($isSet ? "Défini" : "Non défini") . "\n";
    
    if ($isSet && $var !== 'CLOUDINARY_SECRET') {
        // Afficher les 3 premiers caractères pour vérifier que c'est bien la bonne valeur
        // sans exposer la clé complète
        $firstChars = substr($value, 0, 3);
        echo "  Premiers caractères: $firstChars...\n";
    }
}

echo "\n";

// Vérification de l'URL Cloudinary générée
$cloudinaryUrl = env('CLOUDINARY_URL');
if (!empty($cloudinaryUrl)) {
    echo "Structure de l'URL Cloudinary: ";
    if (preg_match('#^cloudinary://(.+):(.+)@(.+)$#', $cloudinaryUrl)) {
        echo "Valide\n";
    } else {
        echo "Invalide (devrait être au format cloudinary://api_key:api_secret@cloud_name)\n";
    }
}

echo "\n";

// Vérifier si toutes les variables nécessaires sont définies
if ($allSet) {
    echo "✅ Toutes les variables d'environnement nécessaires sont définies.\n";
} else {
    echo "❌ Certaines variables d'environnement sont manquantes.\n";
}

// Tester la connexion à Cloudinary
echo "\n=== Test de connexion à Cloudinary ===\n\n";

try {
    $cloudinary = app('cloudinary');
    echo "✅ L'instance Cloudinary a été créée avec succès.\n";
    
    // Essayer d'uploader une petite image test
    $tempFile = tempnam(sys_get_temp_dir(), 'cloudinary_test_');
    $image = imagecreatetruecolor(10, 10);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    imagepng($image, $tempFile);
    
    $testFolder = "acos_football/test";
    $testPublicId = "test_connection_" . time();
    
    echo "Tentative d'upload d'une image test dans $testFolder/$testPublicId... ";
    
    $uploadResult = cloudinary()->upload($tempFile, [
        'folder' => $testFolder,
        'public_id' => $testPublicId
    ]);
    
    echo "✅ Réussi!\n";
    echo "URL de l'image: " . $uploadResult->getSecurePath() . "\n";
    
    // Supprimer l'image test
    echo "Suppression de l'image test... ";
    cloudinary()->destroy($uploadResult->getPublicId());
    echo "✅ Réussi!\n";
    
    // Nettoyer le fichier temporaire
    unlink($tempFile);
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: \n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Fin de la vérification ===\n";
echo "Si les variables d'environnement ne sont pas définies, vous devez les configurer sur Railway.\n";
echo "Consultez le README_CLOUDINARY.md pour plus d'informations.\n"; 