<?php
/**
 * Diagnostic script pour Cloudinary
 * Permet de vérifier la configuration de Cloudinary sans avoir recours à la console
 */

// Définir les en-têtes pour un affichage JSON propre
header('Content-Type: application/json');

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
    }
}

// Charger les variables d'environnement
loadEnv();

// Récupérer la configuration Cloudinary
$cloudName = getenv('CLOUDINARY_CLOUD_NAME');
$apiKey = getenv('CLOUDINARY_KEY');
$apiSecret = getenv('CLOUDINARY_SECRET');
$cloudinaryUrl = getenv('CLOUDINARY_URL');

// Préparer le résultat
$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'cloudinary_config' => [
        'cloud_name' => $cloudName ?: 'Non défini',
        'api_key' => $apiKey ? substr($apiKey, 0, 3) . '...' : 'Non défini',
        'api_secret' => $apiSecret ? 'Défini (masqué)' : 'Non défini',
        'cloudinary_url' => $cloudinaryUrl ? substr($cloudinaryUrl, 0, 15) . '...' : 'Non défini'
    ],
    'extension_loaded' => [
        'curl' => extension_loaded('curl'),
        'json' => extension_loaded('json'),
        'gd' => extension_loaded('gd'),
    ],
    'cloudinary_sdk_check' => null,
    'test_upload' => null
];

// Vérifier si le SDK Cloudinary est disponible
$cloudinarySdkAvailable = class_exists('CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary') || 
                          class_exists('Cloudinary\Cloudinary');

$result['cloudinary_sdk_check'] = [
    'available' => $cloudinarySdkAvailable,
    'message' => $cloudinarySdkAvailable ? 'SDK Cloudinary trouvé' : 'SDK Cloudinary non trouvé'
];

// Tenter un test de connexion et upload si la configuration est complète
if ($cloudName && $apiKey && $apiSecret) {
    try {
        // Inclure l'autoloader de Composer
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            // Vérifier si nous pouvons initialiser Cloudinary
            if (class_exists('Cloudinary\Cloudinary')) {
                $config = [
                    'cloud' => [
                        'cloud_name' => $cloudName,
                        'api_key' => $apiKey,
                        'api_secret' => $apiSecret
                    ]
                ];
                
                $cloudinary = new Cloudinary\Cloudinary($config);
                
                // Créer une petite image test
                $width = 10;
                $height = 10;
                $tempFile = tempnam(sys_get_temp_dir(), 'cloudinary_test_');
                $image = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($image, 255, 255, 255);
                imagefill($image, 0, 0, $white);
                imagepng($image, $tempFile);
                
                // Tenter l'upload
                $testFolder = "acos_football/test";
                $testPublicId = "diagnostic_test_" . time();
                
                $uploadParams = [
                    'folder' => $testFolder,
                    'public_id' => $testPublicId
                ];
                
                $uploadApi = $cloudinary->uploadApi();
                $uploadResult = $uploadApi->upload($tempFile, $uploadParams);
                
                $result['test_upload'] = [
                    'status' => 'OK',
                    'url' => $uploadResult['secure_url'],
                    'public_id' => $uploadResult['public_id']
                ];
                
                // Supprimer l'image test
                $uploadApi->destroy($uploadResult['public_id']);
                unlink($tempFile);
            } else {
                $result['test_upload'] = [
                    'status' => 'ERREUR',
                    'message' => 'Classe Cloudinary non trouvée malgré autoload'
                ];
            }
        } else {
            $result['test_upload'] = [
                'status' => 'ERREUR',
                'message' => 'Autoloader de Composer non trouvé'
            ];
        }
    } catch (Exception $e) {
        $result['test_upload'] = [
            'status' => 'ERREUR',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
    }
} else {
    $result['test_upload'] = [
        'status' => 'ERREUR',
        'message' => 'Configuration Cloudinary incomplète'
    ];
}

// Afficher le résultat
echo json_encode($result, JSON_PRETTY_PRINT); 