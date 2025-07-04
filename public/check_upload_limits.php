<?php
// Afficher les limites d'upload
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . " seconds<br>";
echo "max_input_time: " . ini_get('max_input_time') . " seconds<br>";

// Afficher le chemin du php.ini utilisé
echo "php.ini path: " . php_ini_loaded_file() . "<br>";

// Afficher la configuration de Cloudinary
echo "<h2>Configuration Cloudinary</h2>";
echo "CLOUDINARY_CLOUD_NAME: " . (getenv('CLOUDINARY_CLOUD_NAME') ? 'Défini' : 'Non défini') . "<br>";
echo "CLOUDINARY_KEY: " . (getenv('CLOUDINARY_KEY') ? 'Défini' : 'Non défini') . "<br>";
echo "CLOUDINARY_SECRET: " . (getenv('CLOUDINARY_SECRET') ? 'Défini' : 'Non défini') . "<br>";
echo "CLOUDINARY_URL: " . (getenv('CLOUDINARY_URL') ? 'Défini' : 'Non défini') . "<br>";

// Afficher les variables d'environnement Laravel
echo "<h2>Variables d'environnement Laravel</h2>";
if (file_exists(__DIR__ . '/../.env')) {
    echo "Fichier .env trouvé<br>";
    $env = file_get_contents(__DIR__ . '/../.env');
    $lines = explode("\n", $env);
    foreach ($lines as $line) {
        if (strpos($line, 'CLOUDINARY_') === 0) {
            echo htmlspecialchars($line) . "<br>";
        }
    }
} else {
    echo "Fichier .env non trouvé<br>";
} 