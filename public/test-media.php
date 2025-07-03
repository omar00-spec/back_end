<?php
/**
 * Script de test pour vérifier l'accessibilité des fichiers média
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonction pour vérifier si un fichier est accessible
function checkFileAccess($path, $type = 'file') {
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/');
    $exists = file_exists($fullPath);
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/$path";
    
    echo "<div style='margin-bottom: 10px; padding: 10px; border: 1px solid #ddd;'>";
    echo "<h3>Test de $type: $path</h3>";
    echo "Chemin complet: $fullPath<br>";
    echo "Existe sur le disque: " . ($exists ? "✅ OUI" : "❌ NON") . "<br>";
    
    if ($exists) {
        echo "Taille: " . filesize($fullPath) . " octets<br>";
        echo "Permissions: " . substr(sprintf('%o', fileperms($fullPath)), -4) . "<br>";
    }
    
    echo "URL d'accès: <a href='$url' target='_blank'>$url</a><br>";
    
    // Tester l'accès HTTP
    $headers = get_headers($url);
    $statusCode = substr($headers[0], 9, 3);
    echo "Code de statut HTTP: " . $statusCode . " - " . ($statusCode == "200" ? "✅ OK" : "❌ ERREUR") . "<br>";
    
    if ($type == 'image' && $exists) {
        echo "<img src='$url' style='max-width: 200px; max-height: 200px; border: 1px solid #ccc;' onerror=\"this.onerror=null;this.src='';this.alt='Image non chargée';this.style.padding='10px';\" />";
    } elseif ($type == 'video' && $exists) {
        echo "<video src='$url' controls style='max-width: 320px; max-height: 240px;'></video>";
    }
    
    echo "</div>";
}

// Récupérer les chemins depuis la base de données si possible
$mediaFromDb = [];
try {
    // Charger l'environnement Laravel
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    // Récupérer quelques médias depuis la base de données
    $mediaFromDb = DB::table('media')->select('id', 'type', 'title', 'file_path')->limit(5)->get();
    echo "<h2>Médias trouvés dans la base de données:</h2>";
    
    foreach ($mediaFromDb as $media) {
        echo "<div style='margin-bottom: 10px; padding: 10px; border: 1px solid #ddd;'>";
        echo "ID: {$media->id}, Type: {$media->type}, Titre: {$media->title}<br>";
        echo "Chemin: {$media->file_path}<br>";
        
        // Tester l'accès au fichier
        $path = $media->file_path;
        if (!filter_var($path, FILTER_VALIDATE_URL)) {
            // Si c'est un chemin relatif, tester différentes combinaisons
            $paths = [
                $path,
                "storage/{$path}",
                "storage/media/{$path}",
                "storage/app/public/{$path}",
                "storage/app/public/media/{$path}"
            ];
            
            foreach ($paths as $testPath) {
                checkFileAccess($testPath, $media->type);
            }
        } else {
            echo "URL externe: <a href='{$path}' target='_blank'>{$path}</a>";
        }
        
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red; margin: 10px 0; padding: 10px; border: 1px solid red;'>";
    echo "Erreur lors de l'accès à la base de données: " . $e->getMessage();
    echo "</div>";
}

// Tester quelques chemins standards
echo "<h2>Tests de chemins standards:</h2>";

// Test du lien symbolique storage
checkFileAccess('storage', 'directory');

// Test du répertoire media
checkFileAccess('storage/media', 'directory');

// Tester quelques images connues
$testImages = [
    'storage/media/logo-ACOS.png',
    'storage/media/IMG-20250601-WA0020.jpg',
    'storage/media/WffU0TV.jpg',
    'storage/media/14.jpg',
    'storage/media/488464394_122149547216532835_8145285204593842495_n.jpg'
];

foreach ($testImages as $image) {
    checkFileAccess($image, 'image');
}

// Tester une vidéo
checkFileAccess('storage/media/acos_video.mp4', 'video');
?>

<style>
body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    margin: 20px;
    padding: 0;
    color: #333;
}
h1, h2, h3 {
    color: #2c3e50;
}
a {
    color: #3498db;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style> 