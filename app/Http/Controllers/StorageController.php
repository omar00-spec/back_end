<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class StorageController extends Controller
{
    /**
     * Sert un fichier média depuis storage/media
     */
    public function serveMedia($filename)
    {
        // Chemin complet vers le fichier
        $path = public_path('storage/media/' . $filename);
        
        // Vérifier si le fichier existe
        if (!file_exists($path)) {
            // Essayer de trouver le fichier dans storage/app/public/media
            $altPath = storage_path('app/public/media/' . $filename);
            if (file_exists($altPath)) {
                // Créer le répertoire de destination si nécessaire
                $destDir = public_path('storage/media');
                if (!file_exists($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                
                // Copier le fichier vers public/storage/media
                copy($altPath, $path);
            } else {
                abort(404, 'Fichier non trouvé');
            }
        }
        
        // Déterminer le type MIME
        $type = File::mimeType($path);
        
        // Servir le fichier avec les en-têtes CORS appropriés
        return response()->file($path, [
            'Content-Type' => $type,
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With'
        ]);
    }
} 