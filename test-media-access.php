<?php

/**
 * Script pour tester l'accès aux médias depuis le frontend Netlify
 * Ce script génère une page HTML qui teste l'accès aux médias depuis le frontend Netlify
 */

// Charger les dépendances Laravel
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Désactiver la mise en cache du navigateur
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: text/html; charset=utf-8');

// Récupérer tous les médias
$medias = Media::all();

// Générer la page HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test d'accès aux médias</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1, h2 {
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .media-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            background-color: white;
        }
        .media-item img {
            max-width: 100%;
            height: auto;
            display: block;
            margin-bottom: 10px;
        }
        .media-item video {
            max-width: 100%;
            height: auto;
            display: block;
            margin-bottom: 10px;
        }
        .media-info {
            font-size: 14px;
        }
        .status {
            padding: 5px;
            border-radius: 3px;
            font-weight: bold;
            text-align: center;
            margin-top: 10px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .test-urls {
            margin-top: 20px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
        }
        .url-test {
            margin-bottom: 10px;
        }
        .url-test code {
            background-color: #f8f9fa;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test d'accès aux médias</h1>
        <p>Cette page teste l'accès aux médias depuis le frontend Netlify vers le backend Railway.</p>
        
        <div class="test-urls">
            <h2>URLs de test</h2>
            <div class="url-test">
                <p><strong>Backend Railway:</strong> <code>https://backend-production-b4aa.up.railway.app</code></p>
                <p><strong>Frontend Netlify:</strong> <code>https://heroic-gaufre-c8e8ae.netlify.app</code></p>
            </div>
            
            <h3>Test de fichier</h3>
            <p>Accès au fichier de test: <a href="https://backend-production-b4aa.up.railway.app/storage/media/test-railway.txt" target="_blank">test-railway.txt</a></p>
        </div>
        
        <h2>Médias dans la base de données (<?php echo count($medias); ?>)</h2>
        
        <div class="media-grid">
            <?php foreach ($medias as $media): ?>
                <div class="media-item">
                    <h3><?php echo htmlspecialchars($media->title); ?></h3>
                    
                    <?php
                    // Formater l'URL du média
                    $fileName = basename($media->file_path);
                    $mediaUrl = "https://backend-production-b4aa.up.railway.app/storage/media/{$fileName}";
                    
                    // Afficher le média en fonction de son type
                    if ($media->type === 'photo'):
                    ?>
                        <img src="<?php echo $mediaUrl; ?>" alt="<?php echo htmlspecialchars($media->title); ?>" onerror="this.onerror=null; this.src='https://via.placeholder.com/400x200?text=Image+non+disponible'; this.nextElementSibling.className='status error'; this.nextElementSibling.textContent='Erreur: Image non accessible';">
                        <div class="status pending">Chargement...</div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const img = document.currentScript.previousElementSibling.previousElementSibling;
                                img.onload = function() {
                                    this.nextElementSibling.className = 'status success';
                                    this.nextElementSibling.textContent = 'Succès: Image accessible';
                                };
                            });
                        </script>
                    <?php elseif ($media->type === 'video'): ?>
                        <?php if (strpos($media->file_path, 'youtube') !== false || strpos($media->file_path, 'vimeo') !== false): ?>
                            <iframe width="100%" height="150" src="<?php echo $media->file_path; ?>" frameborder="0" allowfullscreen></iframe>
                            <div class="status success">Vidéo externe (<?php echo strpos($media->file_path, 'youtube') !== false ? 'YouTube' : 'Vimeo'; ?>)</div>
                        <?php else: ?>
                            <video width="100%" controls>
                                <source src="<?php echo $mediaUrl; ?>" type="video/mp4">
                                Votre navigateur ne prend pas en charge la lecture de vidéos.
                            </video>
                            <div class="status pending">Statut inconnu (vérifier manuellement)</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div>Type de média non pris en charge: <?php echo htmlspecialchars($media->type); ?></div>
                    <?php endif; ?>
                    
                    <div class="media-info">
                        <p><strong>ID:</strong> <?php echo $media->id; ?></p>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($media->type); ?></p>
                        <p><strong>Chemin original:</strong> <code><?php echo htmlspecialchars($media->file_path); ?></code></p>
                        <p><strong>URL générée:</strong> <code><?php echo htmlspecialchars($mediaUrl); ?></code></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        // Script pour vérifier l'état des images après chargement
        document.addEventListener('DOMContentLoaded', function() {
            // Attendre que toutes les images soient chargées ou en erreur
            setTimeout(function() {
                const pendingStatuses = document.querySelectorAll('.status.pending');
                pendingStatuses.forEach(function(status) {
                    // Si le statut est toujours en attente après 5 secondes, le marquer comme inconnu
                    if (status.textContent === 'Chargement...') {
                        status.className = 'status pending';
                        status.textContent = 'Statut inconnu (vérifier manuellement)';
                    }
                });
            }, 5000);
        });
    </script>
</body>
</html> 