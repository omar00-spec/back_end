<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Pour les requêtes OPTIONS (preflight), renvoyer directement la réponse avec les en-têtes CORS
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200);
        } else {
            $response = $next($request);
        }

        // Récupérer l'origine de la requête
        $origin = $request->header('Origin');
        
        // Liste des origines autorisées
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:5173',
            'https://heroic-gaufre-c8e8ae.netlify.app',
            'https://heroic-gaufre-c8e8ae.netlify.app/',
            // Ajouter toute autre origine nécessaire
        ];
        
        // Vérifier si l'origine est autorisée ou autoriser toutes les origines en développement
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        } else if (env('APP_ENV') === 'local' || env('APP_ENV') === 'development') {
            // En environnement de développement, on peut être plus permissif
            $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
        }
        
        // En-têtes CORS standards
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400'); // 24 heures
        
        return $response;
    }
}
