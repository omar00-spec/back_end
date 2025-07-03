<?php
/**
 * Proxy CORS pour les fichiers médias
 * Ce script permet de servir les fichiers médias avec les bons en-têtes CORS
 */

// Définir les en-têtes CORS pour permettre l'accès depuis n'importe quel domaine
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Si c'est une requête OPTIONS (preflight), renvoyer juste les en-têtes
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Récupérer le nom du fichier depuis le paramètre GET
$file = isset($_GET["file"]) ? $_GET["file"] : null;

if (!$file) {
    header("HTTP/1.0 400 Bad Request");
    echo "Paramètre file manquant";
    exit;
}

// Sécurité: éviter les traversées de répertoire
$file = str_replace("../", "", $file);

// Chercher le fichier dans plusieurs emplacements possibles
$possiblePaths = [
    __DIR__ . "/storage/media/" . $file,
    __DIR__ . "/public/storage/media/" . $file,
    __DIR__ . "/storage/app/public/media/" . $file,
    __DIR__ . "/../storage/app/public/media/" . $file
];

$fullPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $fullPath = $path;
        break;
    }
}

if (!$fullPath) {
    header("HTTP/1.0 404 Not Found");
    echo "Fichier non trouvé: " . $file;
    echo "<br>Chemins vérifiés:<br>";
    echo implode("<br>", $possiblePaths);
    exit;
}

// Déterminer le type MIME en fonction de l'extension du fichier
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

// Désactiver la mise en cache pour le débogage
// En production, vous pourriez vouloir activer la mise en cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Envoyer le fichier
readfile($fullPath); 