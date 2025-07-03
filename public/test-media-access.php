<?php
// Ce script teste l'accès aux fichiers médias sans dépendre de la base de données

// Afficher les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonction pour vérifier si un fichier est accessible via HTTP
function check_url_exists($url) {
    $headers = @get_headers($url);
    return $headers && strpos($headers[0], '200') !== false;
}

// Fonction pour lister les fichiers dans un répertoire
function list_files($dir) {
    $files = [];
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file != "." && $file != "..") {
                    $files[] = $file;
                }
            }
            closedir($dh);
        }
    }
    return $files;
}

// Vérifier la structure des répertoires
echo "<h1>Test d'accès aux médias</h1>";

// Vérifier si le répertoire public/storage existe
$publicStoragePath = __DIR__ . '/storage';
if (file_exists($publicStoragePath)) {
    echo "<p style='color:green'>✓ Le répertoire public/storage existe</p>";
    
    // Vérifier si c'est un lien symbolique
    if (is_link($publicStoragePath)) {
        echo "<p>Le répertoire public/storage est un lien symbolique qui pointe vers: " . readlink($publicStoragePath) . "</p>";
    }
} else {
    echo "<p style='color:red'>✗ Le répertoire public/storage n'existe pas!</p>";
}

// Vérifier si le répertoire public/storage/media existe
$mediaPath = __DIR__ . '/storage/media';
if (file_exists($mediaPath)) {
    echo "<p style='color:green'>✓ Le répertoire public/storage/media existe</p>";
    
    // Lister les fichiers
    $files = list_files($mediaPath);
    echo "<p>Nombre de fichiers dans public/storage/media: " . count($files) . "</p>";
    
    // Afficher les 10 premiers fichiers
    echo "<h2>Exemples de fichiers:</h2>";
    echo "<ul>";
    $count = 0;
    foreach ($files as $file) {
        if ($count >= 10) break;
        echo "<li>{$file}</li>";
        $count++;
    }
    echo "</ul>";
    
    // Tester l'accès à quelques fichiers
    if (!empty($files)) {
        echo "<h2>Test d'accès aux fichiers:</h2>";
        echo "<ul>";
        
        $baseUrl = isset($_SERVER['HTTP_HOST']) ? 
            ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST']) :
            "https://backend-production-b4aa.up.railway.app";
        
        $testFiles = array_slice($files, 0, 5);
        foreach ($testFiles as $file) {
            $url1 = "{$baseUrl}/storage/media/{$file}";
            $url2 = "{$baseUrl}/public/storage/media/{$file}";
            
            $exists1 = check_url_exists($url1);
            $exists2 = check_url_exists($url2);
            
            echo "<li>{$file}: ";
            echo "<a href='{$url1}' target='_blank'>/storage/media/</a> - " . 
                 ($exists1 ? "<span style='color:green'>OK</span>" : "<span style='color:red'>ERREUR</span>");
            echo " | <a href='{$url2}' target='_blank'>/public/storage/media/</a> - " . 
                 ($exists2 ? "<span style='color:green'>OK</span>" : "<span style='color:red'>ERREUR</span>");
            echo "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color:red'>✗ Le répertoire public/storage/media n'existe pas!</p>";
}

// Vérifier si le répertoire storage/app/public/media existe
$storageAppPublicPath = dirname(__DIR__) . '/storage/app/public/media';
if (file_exists($storageAppPublicPath)) {
    echo "<p style='color:green'>✓ Le répertoire storage/app/public/media existe</p>";
    
    // Lister les fichiers
    $files = list_files($storageAppPublicPath);
    echo "<p>Nombre de fichiers dans storage/app/public/media: " . count($files) . "</p>";
} else {
    echo "<p style='color:red'>✗ Le répertoire storage/app/public/media n'existe pas!</p>";
}

// Afficher les informations sur le serveur
echo "<h2>Informations sur le serveur</h2>";
echo "<pre>";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'Non défini') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Non défini') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Non défini') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Non défini') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'Non défini') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Non défini') . "\n";
echo "</pre>";

// Afficher des liens de test
echo "<h2>Liens de test</h2>";
echo "<p>Cliquez sur ces liens pour tester l'accès direct:</p>";
echo "<ul>";
echo "<li><a href='/storage/media/acos_video.mp4' target='_blank'>/storage/media/acos_video.mp4</a></li>";
echo "<li><a href='/public/storage/media/acos_video.mp4' target='_blank'>/public/storage/media/acos_video.mp4</a></li>";
echo "</ul>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test d'accès aux médias</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Test d'accès aux médias</h1>
    
    <h2>Informations sur le serveur</h2>
    <pre><?php print_r($_SERVER); ?></pre>
    
    <h2>Test d'accès direct</h2>
    <p>Essayez d'accéder directement à ces URLs:</p>
    <ul>
        <li><a href="/storage/media/acos_video.mp4" target="_blank">/storage/media/acos_video.mp4</a></li>
        <li><a href="/public/storage/media/acos_video.mp4" target="_blank">/public/storage/media/acos_video.mp4</a></li>
    </ul>
</body>
</html> 