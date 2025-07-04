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
        $response = $next($request);

        // Récupérer l'origine de la requête
        $origin = $request->header('Origin');
        
        // Liste des origines autorisées
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:5173',
            'https://heroic-gaufre-c8e8ae.netlify.app',
            'https://heroic-gaufre-c8e8ae.netlify.app/',
            // Ajouter toutes les origines possibles
            $origin // Autoriser dynamiquement l'origine de la requête
        ];
        
        // Vérifier si l'origine est autorisée
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }
        
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-File-Name, X-File-Size, X-File-Type, Accept, Origin');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400'); // 24 heures

        return $response;
    }
}
