<?php
/**
 * Script pour corriger les problèmes CORS
 * Exécutez ce script sur votre serveur Railway
 */

// Vérifier si le fichier .htaccess existe à la racine
$htaccessPath = __DIR__ . '/.htaccess';
$htaccessContent = <<<EOT
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "https://heroic-gaufre-c8e8ae.netlify.app"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Origin, X-Requested-With, Content-Type, Accept, Authorization"
    Header set Access-Control-Allow-Credentials "false"
    
    # Répondre aux requêtes OPTIONS avec un 200 OK
    RewriteEngine On
    RewriteRule ^(.*)$ $1 [R=200,L]
    Header always set Access-Control-Allow-Origin "https://heroic-gaufre-c8e8ae.netlify.app"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Origin, X-Requested-With, Content-Type, Accept, Authorization"
</IfModule>
EOT;

file_put_contents($htaccessPath, $htaccessContent, FILE_APPEND);
echo "Fichier .htaccess mis à jour avec les règles CORS.<br>";

// Modifier le fichier de configuration CORS
$corsConfigPath = __DIR__ . '/config/cors.php';
$corsConfig = file_get_contents($corsConfigPath);

// Remplacer les origines autorisées
$corsConfig = preg_replace(
    "/'allowed_origins' => \[(.*?)\]/s",
    "'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:5173', 
        'http://localhost:3001', 
        'http://localhost:8080', 
        'http://localhost:8000', 
        'https://backend-production-ea54.up.railway.app',
        'https://backend-production-b4aa.up.railway.app',
        'https://heroic-gaufre-c8e8ae.netlify.app',
        '*'
    ]",
    $corsConfig
);

// S'assurer que supports_credentials est à false
$corsConfig = preg_replace(
    "/'supports_credentials' => (true|false)/",
    "'supports_credentials' => false",
    $corsConfig
);

file_put_contents($corsConfigPath, $corsConfig);
echo "Configuration CORS mise à jour.<br>";

// Modifier le middleware CORS personnalisé
$corsMiddlewarePath = __DIR__ . '/app/Http/Middleware/CorsMiddleware.php';
$corsMiddleware = <<<EOT
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  \$request
     * @param  \Closure  \$next
     * @return mixed
     */
    public function handle(Request \$request, Closure \$next)
    {
        // Pour les requêtes OPTIONS (pre-flight)
        if (\$request->isMethod('OPTIONS')) {
            \$response = response('', 200);
        } else {
            \$response = \$next(\$request);
        }

        // Définir l'origine spécifique
        \$response->headers->set('Access-Control-Allow-Origin', 'https://heroic-gaufre-c8e8ae.netlify.app');
        \$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        \$response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
        \$response->headers->set('Access-Control-Allow-Credentials', 'false');
        \$response->headers->set('Access-Control-Max-Age', '86400');

        return \$response;
    }
}
EOT;

file_put_contents($corsMiddlewarePath, $corsMiddleware);
echo "Middleware CORS mis à jour.<br>";

echo "<h2>Corrections CORS terminées</h2>";
echo "<p>Veuillez redémarrer votre application Laravel sur Railway pour appliquer les changements.</p>"; 