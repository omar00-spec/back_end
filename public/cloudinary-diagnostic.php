<?php
/**
 * Diagnostic Cloudinary - ACOS Football
 * Script accessible via navigateur pour vérifier la configuration Cloudinary
 */

// Pour un affichage plus agréable en cas d'erreur
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Définir les en-têtes
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Cloudinary - ACOS Football</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .warning {
            color: #f39c12;
            font-weight: bold;
        }
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Diagnostic Cloudinary - ACOS Football</h1>

<?php
// Fonction pour charger les variables d'environnement
function loadEnv() {
    $envFile = __DIR__ . '/../.env';
    
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignorer les commentaires
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Récupérer les variables d'environnement
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Retirer les guillemets si présents
                if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                    $value = substr($value, 1, -1);
                } elseif (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) {
                    $value = substr($value, 1, -1);
                }
                
                // Définir la variable d'environnement
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
        return true;
    }
    return false;
}

// Fonction pour vérifier si une extension est chargée
function checkExtension($name) {
    $loaded = extension_loaded($name);
    echo '<tr>';
    echo '<td>' . htmlspecialchars($name) . '</td>';
    echo '<td>';
    if ($loaded) {
        echo '<span class="success">Chargée ✓</span>';
    } else {
        echo '<span class="error">Non chargée ✗</span>';
    }
    echo '</td>';
    echo '</tr>';
    return $loaded;
}

// Fonction pour vérifier la configuration Cloudinary
function checkCloudinaryConfig() {
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
    $apiKey = getenv('CLOUDINARY_KEY');
    $apiSecret = getenv('CLOUDINARY_SECRET');
    $cloudinaryUrl = getenv('CLOUDINARY_URL');
    
    echo '<div class="section">';
    echo '<h2>Configuration Cloudinary</h2>';
    echo '<table>';
    echo '<tr><th>Variable</th><th>Statut</th></tr>';
    
    // Vérifier CLOUDINARY_CLOUD_NAME
    echo '<tr>';
    echo '<td>CLOUDINARY_CLOUD_NAME</td>';
    echo '<td>';
    if ($cloudName) {
        echo '<span class="success">Défini ✓ (' . htmlspecialchars($cloudName) . ')</span>';
    } else {
        echo '<span class="error">Non défini ✗</span>';
    }
    echo '</td>';
    echo '</tr>';
    
    // Vérifier CLOUDINARY_KEY
    echo '<tr>';
    echo '<td>CLOUDINARY_KEY</td>';
    echo '<td>';
    if ($apiKey) {
        echo '<span class="success">Défini ✓ (' . htmlspecialchars(substr($apiKey, 0, 3) . '...') . ')</span>';
    } else {
        echo '<span class="error">Non défini ✗</span>';
    }
    echo '</td>';
    echo '</tr>';
    
    // Vérifier CLOUDINARY_SECRET
    echo '<tr>';
    echo '<td>CLOUDINARY_SECRET</td>';
    echo '<td>';
    if ($apiSecret) {
        echo '<span class="success">Défini ✓ (masqué)</span>';
    } else {
        echo '<span class="error">Non défini ✗</span>';
    }
    echo '</td>';
    echo '</tr>';
    
    // Vérifier CLOUDINARY_URL
    echo '<tr>';
    echo '<td>CLOUDINARY_URL</td>';
    echo '<td>';
    if ($cloudinaryUrl) {
        echo '<span class="success">Défini ✓ (' . htmlspecialchars(substr($cloudinaryUrl, 0, 15) . '...') . ')</span>';
    } else {
        echo '<span class="error">Non défini ✗</span>';
        
        if ($cloudName && $apiKey && $apiSecret) {
            echo '<br><span class="warning">Suggestion de format: cloudinary://' . htmlspecialchars($apiKey) . ':' . htmlspecialchars(substr($apiSecret, 0, 3)) . '...@' . htmlspecialchars($cloudName) . '</span>';
        }
    }
    echo '</td>';
    echo '</tr>';
    
    echo '</table>';
    
    // Résumé de la configuration
    echo '<div style="margin-top: 15px;">';
    if ($cloudName && $apiKey && $apiSecret && $cloudinaryUrl) {
        echo '<p class="success">✓ Configuration Cloudinary complète</p>';
    } else {
        echo '<p class="error">✗ Configuration Cloudinary incomplète</p>';
    }
    echo '</div>';
    
    echo '</div>';
    
    return [
        'cloud_name' => $cloudName,
        'api_key' => $apiKey,
        'api_secret' => $apiSecret,
        'cloudinary_url' => $cloudinaryUrl
    ];
}

// Fonction pour vérifier les extensions PHP requises
function checkRequiredExtensions() {
    echo '<div class="section">';
    echo '<h2>Extensions PHP requises</h2>';
    echo '<table>';
    echo '<tr><th>Extension</th><th>Statut</th></tr>';
    
    $curl = checkExtension('curl');
    $json = checkExtension('json');
    $gd = checkExtension('gd');
    
    echo '</table>';
    
    // Résumé des extensions
    echo '<div style="margin-top: 15px;">';
    if ($curl && $json && $gd) {
        echo '<p class="success">✓ Toutes les extensions requises sont disponibles</p>';
    } else {
        echo '<p class="error">✗ Certaines extensions requises sont manquantes</p>';
    }
    echo '</div>';
    
    echo '</div>';
    
    return $curl && $json && $gd;
}

// Fonction pour vérifier l'accès à l'autoloader Composer
function checkComposerAutoload() {
    echo '<div class="section">';
    echo '<h2>Accès à Composer</h2>';
    
    $autoloaderPath = __DIR__ . '/../vendor/autoload.php';
    $autoloaderExists = file_exists($autoloaderPath);
    
    if ($autoloaderExists) {
        echo '<p class="success">✓ Autoloader Composer trouvé à : ' . htmlspecialchars($autoloaderPath) . '</p>';
        
        try {
            require_once $autoloaderPath;
            echo '<p class="success">✓ Autoloader Composer chargé avec succès</p>';
            $result = true;
        } catch (Exception $e) {
            echo '<p class="error">✗ Erreur lors du chargement de l\'autoloader : ' . htmlspecialchars($e->getMessage()) . '</p>';
            $result = false;
        }
    } else {
        echo '<p class="error">✗ Autoloader Composer non trouvé à : ' . htmlspecialchars($autoloaderPath) . '</p>';
        $result = false;
    }
    
    echo '</div>';
    
    return $result;
}

// Fonction pour tester la disponibilité du SDK Cloudinary
function checkCloudinarySdk() {
    echo '<div class="section">';
    echo '<h2>SDK Cloudinary</h2>';
    
    // Vérifier CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary
    $cloudinaryLaravelExists = class_exists('CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary');
    
    echo '<p>';
    if ($cloudinaryLaravelExists) {
        echo '<span class="success">✓ Package CloudinaryLabs\CloudinaryLaravel trouvé</span>';
    } else {
        echo '<span class="warning">⚠ Package CloudinaryLabs\CloudinaryLaravel non trouvé</span>';
    }
    echo '</p>';
    
    // Vérifier Cloudinary\Cloudinary
    $cloudinarySdkExists = class_exists('Cloudinary\Cloudinary');
    
    echo '<p>';
    if ($cloudinarySdkExists) {
        echo '<span class="success">✓ Package Cloudinary\Cloudinary trouvé</span>';
    } else {
        echo '<span class="warning">⚠ Package Cloudinary\Cloudinary non trouvé</span>';
    }
    echo '</p>';
    
    // Résumé SDK
    echo '<div style="margin-top: 15px;">';
    if ($cloudinaryLaravelExists || $cloudinarySdkExists) {
        echo '<p class="success">✓ Au moins une version du SDK Cloudinary est disponible</p>';
        $result = true;
    } else {
        echo '<p class="error">✗ Aucun SDK Cloudinary n\'est disponible</p>';
        echo '<p>Vérifiez que les packages suivants sont installés :</p>';
        echo '<pre>composer require cloudinary-labs/cloudinary-laravel</pre>';
        echo '<p>ou</p>';
        echo '<pre>composer require cloudinary/cloudinary_php</pre>';
        $result = false;
    }
    echo '</div>';
    
    echo '</div>';
    
    return $result;
}

// Fonction pour tester un upload vers Cloudinary
function testCloudinaryUpload($config) {
    echo '<div class="section">';
    echo '<h2>Test d\'upload Cloudinary</h2>';
    
    if (!$config['cloud_name'] || !$config['api_key'] || !$config['api_secret']) {
        echo '<p class="error">✗ Impossible de tester l\'upload : configuration incomplète</p>';
        echo '</div>';
        return false;
    }
    
    if (!class_exists('Cloudinary\Cloudinary') && !class_exists('CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary')) {
        echo '<p class="error">✗ Impossible de tester l\'upload : SDK Cloudinary non disponible</p>';
        echo '</div>';
        return false;
    }
    
    try {
        // Créer une petite image test
        $width = 10;
        $height = 10;
        $tempFile = tempnam(sys_get_temp_dir(), 'cloudinary_test_');
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        imagepng($image, $tempFile);
        
        echo '<p>Image test créée : ' . htmlspecialchars($tempFile) . '</p>';
        
        // Tenter l'upload avec le SDK direct Cloudinary
        if (class_exists('Cloudinary\Cloudinary')) {
            $cloudinaryConfig = [
                'cloud' => [
                    'cloud_name' => $config['cloud_name'],
                    'api_key' => $config['api_key'],
                    'api_secret' => $config['api_secret']
                ]
            ];
            
            $cloudinary = new Cloudinary\Cloudinary($cloudinaryConfig);
            
            // Tenter l'upload
            $testFolder = "acos_football/test";
            $testPublicId = "diagnostic_test_" . time();
            
            $uploadParams = [
                'folder' => $testFolder,
                'public_id' => $testPublicId
            ];
            
            echo '<p>Tentative d\'upload vers Cloudinary...</p>';
            
            $uploadApi = $cloudinary->uploadApi();
            $uploadResult = $uploadApi->upload($tempFile, $uploadParams);
            
            echo '<p class="success">✓ Upload réussi !</p>';
            echo '<p>URL de l\'image : <a href="' . htmlspecialchars($uploadResult['secure_url']) . '" target="_blank">' . htmlspecialchars($uploadResult['secure_url']) . '</a></p>';
            echo '<p>Public ID : ' . htmlspecialchars($uploadResult['public_id']) . '</p>';
            
            // Supprimer l'image test
            $uploadApi->destroy($uploadResult['public_id']);
            echo '<p>Image test supprimée de Cloudinary</p>';
            
            $result = true;
        } 
        // Tenter l'upload avec CloudinaryLabs
        elseif (class_exists('CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary')) {
            echo '<p>Utilisation du package CloudinaryLabs\CloudinaryLaravel...</p>';
            
            $result = Cloudinary::upload($tempFile, [
                'folder' => 'acos_football/test',
                'public_id' => 'diagnostic_test_' . time()
            ]);
            
            echo '<p class="success">✓ Upload réussi !</p>';
            echo '<p>URL de l\'image : <a href="' . htmlspecialchars($result->getSecurePath()) . '" target="_blank">' . htmlspecialchars($result->getSecurePath()) . '</a></p>';
            echo '<p>Public ID : ' . htmlspecialchars($result->getPublicId()) . '</p>';
            
            // Supprimer l'image test
            Cloudinary::destroy($result->getPublicId());
            echo '<p>Image test supprimée de Cloudinary</p>';
            
            $result = true;
        }
        
        // Supprimer le fichier temporaire
        if (file_exists($tempFile)) {
            unlink($tempFile);
            echo '<p>Fichier temporaire local supprimé</p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">✗ Erreur lors du test d\'upload : ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>Fichier : ' . htmlspecialchars($e->getFile()) . '</p>';
        echo '<p>Ligne : ' . htmlspecialchars($e->getLine()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        
        $result = false;
    }
    
    echo '</div>';
    
    return $result;
}

// Exécution principale
echo '<div class="section">';
echo '<h2>Informations système</h2>';
echo '<table>';
echo '<tr><th>Élément</th><th>Valeur</th></tr>';
echo '<tr><td>PHP Version</td><td>' . phpversion() . '</td></tr>';
echo '<tr><td>Serveur</td><td>' . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu') . '</td></tr>';
echo '<tr><td>Chemin du script</td><td>' . htmlspecialchars(__FILE__) . '</td></tr>';
echo '</table>';
echo '</div>';

// Charger les variables d'environnement
$envLoaded = loadEnv();

echo '<div class="section">';
echo '<h2>Chargement des variables d\'environnement</h2>';
if ($envLoaded) {
    echo '<p class="success">✓ Variables d\'environnement chargées depuis .env</p>';
} else {
    echo '<p class="warning">⚠ Fichier .env non trouvé, utilisation des variables d\'environnement système</p>';
}
echo '</div>';

// Vérifier la configuration Cloudinary
$config = checkCloudinaryConfig();

// Vérifier les extensions PHP requises
$extensionsOk = checkRequiredExtensions();

// Vérifier l'accès à l'autoloader Composer
$autoloaderOk = checkComposerAutoload();

// Vérifier la disponibilité du SDK Cloudinary
$sdkOk = false;
if ($autoloaderOk) {
    $sdkOk = checkCloudinarySdk();
}

// Tester un upload vers Cloudinary
$uploadOk = false;
if ($extensionsOk && $autoloaderOk && $sdkOk) {
    $uploadOk = testCloudinaryUpload($config);
}

// Résumé du diagnostic
echo '<div class="section">';
echo '<h2>Résumé du diagnostic</h2>';
echo '<table>';
echo '<tr><th>Test</th><th>Résultat</th></tr>';
echo '<tr><td>Variables d\'environnement</td><td>' . ($envLoaded ? '<span class="success">OK</span>' : '<span class="warning">Avertissement</span>') . '</td></tr>';
echo '<tr><td>Configuration Cloudinary</td><td>' . (($config['cloud_name'] && $config['api_key'] && $config['api_secret']) ? '<span class="success">OK</span>' : '<span class="error">Erreur</span>') . '</td></tr>';
echo '<tr><td>Extensions PHP</td><td>' . ($extensionsOk ? '<span class="success">OK</span>' : '<span class="error">Erreur</span>') . '</td></tr>';
echo '<tr><td>Autoloader Composer</td><td>' . ($autoloaderOk ? '<span class="success">OK</span>' : '<span class="error">Erreur</span>') . '</td></tr>';
echo '<tr><td>SDK Cloudinary</td><td>' . ($sdkOk ? '<span class="success">OK</span>' : '<span class="error">Erreur</span>') . '</td></tr>';
echo '<tr><td>Test d\'upload</td><td>' . ($uploadOk ? '<span class="success">OK</span>' : '<span class="error">Erreur</span>') . '</td></tr>';
echo '</table>';

if ($config['cloud_name'] && $config['api_key'] && $config['api_secret'] && $extensionsOk && $autoloaderOk && $sdkOk && $uploadOk) {
    echo '<p class="success" style="font-size: 1.2em; margin-top: 20px;">✅ La configuration Cloudinary semble fonctionnelle !</p>';
} else {
    echo '<p class="error" style="font-size: 1.2em; margin-top: 20px;">❌ Des problèmes ont été détectés avec la configuration Cloudinary.</p>';
}

echo '</div>';

// Recommandations
echo '<div class="section">';
echo '<h2>Recommandations</h2>';
echo '<ul>';

if (!$config['cloud_name'] || !$config['api_key'] || !$config['api_secret']) {
    echo '<li class="error">Configurez correctement les variables d\'environnement Cloudinary dans le fichier .env</li>';
}

if (!$extensionsOk) {
    echo '<li class="error">Assurez-vous que toutes les extensions PHP requises sont installées</li>';
}

if (!$autoloaderOk) {
    echo '<li class="error">Vérifiez l\'installation de Composer et exécutez <code>composer install</code></li>';
}

if (!$sdkOk) {
    echo '<li class="error">Installez le package Cloudinary avec <code>composer require cloudinary-labs/cloudinary-laravel</code></li>';
}

echo '<li>Si tous les tests sont passés mais que vous rencontrez toujours des problèmes, vérifiez les logs Laravel dans <code>storage/logs/laravel.log</code></li>';

echo '</ul>';
echo '</div>';

?>
</body>
</html>
