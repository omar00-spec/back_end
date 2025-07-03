<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class StorageController extends Controller
{
    /**
     * Servir un fichier média directement depuis le système de fichiers
     */
    public function serveMedia($filename)
    {
        // Chemin direct vers le fichier dans public/storage/media
        $path = public_path('storage/media/' . $filename);
        
        // Vérifier si le fichier existe
        if (!File::exists($path)) {
            // Si le fichier n'existe pas, essayer dans storage/app/public/media
            $altPath = storage_path('app/public/media/' . $filename);
            if (File::exists($altPath)) {
                // Copier le fichier vers public/storage/media s'il n'y est pas déjà
                File::copy($altPath, $path);
            } else {
                return response()->json(['error' => 'Fichier non trouvé'], 404);
            }
        }
        
        // Déterminer le type MIME
        $type = File::mimeType($path);
        
        // Servir le fichier avec les en-têtes CORS appropriés
        return Response::make(File::get($path), 200, [
            'Content-Type' => $type,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With'
        ]);
    }
} 