<?php
/**
 * Script de test et de correction des problèmes CORS
 * Ce script permet de vérifier et de corriger les problèmes CORS pour les fichiers médias
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir les en-têtes CORS pour cette page
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

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
    $headers = @get_headers($url);
    if ($headers) {
        $statusCode = substr($headers[0], 9, 3);
        echo "Code de statut HTTP: " . $statusCode . " - " . ($statusCode == "200" ? "✅ OK" : "❌ ERREUR") . "<br>";
        
        // Vérifier les en-têtes CORS
        $hasCorsHeaders = false;
        foreach ($headers as $header) {
            if (strpos($header, 'Access-Control-Allow-Origin') !== false) {
                echo "En-tête CORS trouvé: $header ✅<br>";
                $hasCorsHeaders = true;
            }
        }
        
        if (!$hasCorsHeaders) {
            echo "❌ Aucun en-tête CORS trouvé. Cela peut causer des problèmes d'accès depuis d'autres domaines.<br>";
        }
    } else {
        echo "❌ Impossible de récupérer les en-têtes HTTP.<br>";
    }
    
    if ($type == 'image' && $exists) {
        echo "<img src='$url' style='max-width: 200px; max-height: 200px; border: 1px solid #ccc;' onerror=\"this.onerror=null;this.src='';this.alt='Image non chargée';this.style.padding='10px';\" />";
    } elseif ($type == 'video' && $exists) {
        echo "<video src='$url' controls style='max-width: 320px; max-height: 240px;'></video>";
    }
    
    echo "</div>";
    
    return $exists;
}

// Fonction pour ajouter les en-têtes CORS à un fichier .htaccess
function createHtaccess($directory) {
    $htaccessContent = "# Activer le module headers si ce n'est pas déjà fait
<IfModule mod_headers.c>
    # Autoriser l'accès CORS depuis n'importe quelle origine
    Header set Access-Control-Allow-Origin \"*\"
    Header set Access-Control-Allow-Methods \"GET, OPTIONS\"
    Header set Access-Control-Allow-Headers \"Content-Type, Authorization, X-Requested-With\"
    
    # Définir le type MIME correct pour les vidéos MP4
    <FilesMatch \"\\.(mp4|MP4)$\">
        Header set Content-Type \"video/mp4\"
    </FilesMatch>
    
    # Définir le type MIME correct pour les vidéos WebM
    <FilesMatch \"\\.(webm|WEBM)$\">
        Header set Content-Type \"video/webm\"
    </FilesMatch>
    
    # Définir le type MIME correct pour les images
    <FilesMatch \"\\.(jpg|jpeg|png|gif)$\">
        Header set Content-Type \"image/%{FILEEXT}\"
    </FilesMatch>
</IfModule>

# Désactiver le cache pour le débogage
<IfModule mod_headers.c>
    Header set Cache-Control \"no-cache, no-store, must-revalidate\"
    Header set Pragma \"no-cache\"
    Header set Expires 0
</IfModule>

# Permettre le listage des fichiers pour le débogage
Options +Indexes";

    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($directory, '/');
    
    // Créer le répertoire s'il n'existe pas
    if (!file_exists($fullPath)) {
        if (mkdir($fullPath, 0755, true)) {
            echo "<div class='success'>✅ Répertoire créé: $fullPath</div>";
        } else {
            echo "<div class='error'>❌ Impossible de créer le répertoire: $fullPath</div>";
            return false;
        }
    }
    
    // Créer le fichier .htaccess
    $htaccessPath = $fullPath . '/.htaccess';
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        echo "<div class='success'>✅ Fichier .htaccess créé dans: $directory</div>";
        return true;
    } else {
        echo "<div class='error'>❌ Impossible de créer le fichier .htaccess dans: $directory</div>";
        return false;
    }
}

// Vérifier si une action est demandée
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'create_htaccess':
            $directories = [
                'storage',
                'storage/media',
                'public/storage',
                'public/storage/media'
            ];
            
            foreach ($directories as $dir) {
                createHtaccess($dir);
            }
            break;
            
        case 'fix_cors':
            // Créer un fichier PHP proxy pour servir les fichiers médias avec les bons en-têtes CORS
            $proxyContent = '<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

$file = isset($_GET["file"]) ? $_GET["file"] : null;

if (!$file) {
    header("HTTP/1.0 400 Bad Request");
    echo "Paramètre file manquant";
    exit;
}

// Sécurité: éviter les traversées de répertoire
$file = str_replace("../", "", $file);
$fullPath = __DIR__ . "/storage/media/" . $file;

if (!file_exists($fullPath)) {
    header("HTTP/1.0 404 Not Found");
    echo "Fichier non trouvé";
    exit;
}

$extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

switch ($extension) {
    case "mp4":
        header("Content-Type: video/mp4");
        break;
    case "webm":
        header("Content-Type: video/webm");
        break;
    case "jpg":
    case "jpeg":
        header("Content-Type: image/jpeg");
        break;
    case "png":
        header("Content-Type: image/png");
        break;
    case "gif":
        header("Content-Type: image/gif");
        break;
    default:
        header("Content-Type: application/octet-stream");
}

// Désactiver la mise en cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Envoyer le fichier
readfile($fullPath);
';
            
            $proxyPath = $_SERVER['DOCUMENT_ROOT'] . '/media-proxy.php';
            if (file_put_contents($proxyPath, $proxyContent)) {
                echo "<div class='success'>✅ Proxy CORS créé: media-proxy.php</div>";
                echo "<p>Vous pouvez maintenant accéder aux fichiers médias via: <code>https://backend-production-b4aa.up.railway.app/media-proxy.php?file=nom_du_fichier.mp4</code></p>";
            } else {
                echo "<div class='error'>❌ Impossible de créer le proxy CORS</div>";
            }
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test et correction CORS</title>
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
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .action-button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 5px;
            text-decoration: none;
        }
        .action-button:hover {
            background-color: #2980b9;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <h1>Test et correction des problèmes CORS</h1>
    
    <div>
        <a href="?action=create_htaccess" class="action-button">Créer les fichiers .htaccess</a>
        <a href="?action=fix_cors" class="action-button">Créer un proxy CORS</a>
    </div>
    
    <h2>Vérification des répertoires</h2>
    <?php
    $directories = [
        'storage' => 'directory',
        'storage/media' => 'directory',
        'public/storage' => 'directory',
        'public/storage/media' => 'directory'
    ];
    
    foreach ($directories as $dir => $type) {
        checkFileAccess($dir, $type);
    }
    ?>
    
    <h2>Test des fichiers médias</h2>
    <?php
    // Tester quelques images connues
    $testMedia = [
        'storage/media/logo-ACOS.png' => 'image',
        'storage/media/IMG-20250601-WA0020.jpg' => 'image',
        'storage/media/acos_video.mp4' => 'video',
        'public/storage/media/logo-ACOS.png' => 'image',
        'public/storage/media/IMG-20250601-WA0020.jpg' => 'image',
        'public/storage/media/acos_video.mp4' => 'video'
    ];
    
    foreach ($testMedia as $path => $type) {
        checkFileAccess($path, $type);
    }
    ?>
    
    <h2>Comment utiliser le proxy CORS</h2>
    <p>Si vous avez créé le proxy CORS, vous pouvez accéder à vos fichiers médias via l'URL:</p>
    <code>https://backend-production-b4aa.up.railway.app/media-proxy.php?file=nom_du_fichier.mp4</code>
    
    <p>Exemple pour accéder à une vidéo:</p>
    <code>https://backend-production-b4aa.up.railway.app/media-proxy.php?file=acos_video.mp4</code>
    
    <h2>Mise à jour du frontend</h2>
    <p>Dans votre frontend, vous devriez mettre à jour les URL des médias pour utiliser le proxy:</p>
    <pre>
// Exemple de code pour le frontend
const fixMediaUrl = (url) => {
  if (!url) return null;
  
  // Si c'est déjà une URL complète externe (YouTube, etc.)
  if (url.includes('youtube.com') || url.includes('vimeo.com')) {
    return url;
  }
  
  // Extraire le nom du fichier
  const fileName = url.split('/').pop();
  
  // Utiliser le proxy CORS
  return `https://backend-production-b4aa.up.railway.app/media-proxy.php?file=${fileName}`;
};
    </pre>
</body>
</html> 