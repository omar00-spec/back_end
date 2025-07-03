<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = Media::query()->with('category');

        // Filtrer par type si spécifié
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filtrer par catégorie si spécifié
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Trier par date de création
        $query->orderBy('created_at', 'desc');

        $media = $query->get();
        
        // Formater les URLs des fichiers pour chaque média
        foreach ($media as $item) {
            $this->formatMediaUrl($item);
        }

        return $media;
    }

    public function store(Request $request)
    {
        $media = Media::create($request->all());
        $this->formatMediaUrl($media);
        return $media;
    }

    public function show(Media $media)
    {
        $media = $media->load('category');
        $this->formatMediaUrl($media);
        return $media;
    }

    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);
        $media->update($request->all());
        $this->formatMediaUrl($media);

        return response()->json([
            'message' => 'Média mis à jour avec succès !',
            'media' => $media
        ]);
    }

    public function destroy(Media $media)
    {
        $media->delete();
        return response()->noContent();
    }

    /**
     * Récupère uniquement les médias de type 'photo'
     */
    public function getPhotos()
    {
        $photos = Media::where('type', 'photo')
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Formater les URLs des fichiers
        foreach ($photos as $photo) {
            $this->formatMediaUrl($photo);
        }

        return $photos;
    }

    /**
     * Récupère uniquement les médias de type 'video'
     */
    public function getVideos()
    {
        $videos = Media::where('type', 'video')
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Formater les URLs des fichiers
        foreach ($videos as $video) {
            $this->formatMediaUrl($video);
        }

        return $videos;
    }

    /**
     * Récupère les médias par catégorie
     */
    public function getByCategory($categoryId)
    {
        $media = Media::where('category_id', $categoryId)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Formater les URLs des fichiers
        foreach ($media as $item) {
            $this->formatMediaUrl($item);
        }

        return $media;
    }
    
    /**
     * Formate l'URL du fichier média pour l'afficher correctement
     */
    public function formatMediaUrl($item)
    {
        if (!$item->file_path) {
            return;
        }
        
        // Si c'est une URL YouTube ou autre service externe, ne pas la modifier
        if (filter_var($item->file_path, FILTER_VALIDATE_URL) && 
            (strpos($item->file_path, 'youtube') !== false || 
             strpos($item->file_path, 'youtu.be') !== false || 
             strpos($item->file_path, 'vimeo') !== false ||
             strpos($item->file_path, 'embed') !== false)) {
            
            // Si c'est une URL YouTube normale, la convertir en format embed
            if ((strpos($item->file_path, 'youtube.com/watch') !== false) && 
                (strpos($item->file_path, 'embed') === false)) {
                
                // Extraire l'ID de la vidéo
                $query = parse_url($item->file_path, PHP_URL_QUERY);
                parse_str($query, $params);
                if (isset($params['v'])) {
                    $videoId = $params['v'];
                    $item->file_path = "https://www.youtube.com/embed/{$videoId}";
                }
            }
            
            // Si c'est une URL courte youtu.be, la convertir en format embed
            if (strpos($item->file_path, 'youtu.be/') !== false) {
                $parts = explode('/', $item->file_path);
                $videoId = end($parts);
                $item->file_path = "https://www.youtube.com/embed/{$videoId}";
            }
            
            return;
        }
        
        // Pour les autres URLs (non YouTube/Vimeo)
        if (filter_var($item->file_path, FILTER_VALIDATE_URL)) {
            return;
        }
        
        // Récupérer le chemin du fichier
        $filePath = $item->file_path;
        $fileName = basename($filePath);
        
        // Utiliser l'URL du backend Railway au lieu de localhost:8000
        $backendDomain = "https://backend-production-b4aa.up.railway.app";
        
        // Vérifier d'abord si le fichier existe directement dans public/storage/media
        $publicPath = public_path('storage/media/' . $fileName);
        if (file_exists($publicPath)) {
            $item->file_path = $backendDomain . '/storage/media/' . $fileName;
            return;
        }
        
        // Ensuite vérifier dans storage/app/public/media
        $storagePath = storage_path('app/public/media/' . $fileName);
        if (file_exists($storagePath)) {
            // Copier le fichier vers public/storage/media s'il n'y est pas déjà
            $this->ensureCopyToPublicStorage('media/' . $fileName, $fileName);
            
            $item->file_path = $backendDomain . '/storage/media/' . $fileName;
            return;
        }
        
        // Si on arrive ici, essayons les autres possibilités
        $possiblePaths = [
            // Chemin absolu
            $filePath,
            // Chemin relatif à media/
            'media/' . $fileName,
            // Chemin storage/media
            'storage/media/' . $fileName
        ];
        
        // Essayer chaque possibilité
        foreach ($possiblePaths as $path) {
            // Si c'est un chemin complet déjà
            if (strpos($path, 'storage/') === 0) {
                if (file_exists(public_path($path))) {
                    $item->file_path = $backendDomain . '/' . $path;
                    return;
                }
            } 
            // Pour les chemins commençant par media/
            else if (strpos($path, 'media/') === 0) {
                $fullPath = 'storage/' . $path;
                if (file_exists(public_path($fullPath))) {
                    $item->file_path = $backendDomain . '/' . $fullPath;
                    return;
                }
                
                // Essayer de copier le fichier vers public/storage/media
                $this->ensureCopyToPublicStorage($path, $fileName);
            }
        }
        
        // Si on arrive ici, aucun des chemins n'a fonctionné
        // On retourne l'URL la plus probable
        $item->file_path = $backendDomain . '/storage/media/' . $fileName;
    }

    /**
     * S'assure que le fichier est copié de storage/app/public/media vers public/storage/media
     */
    protected function ensureCopyToPublicStorage($path, $filename)
    {
        try {
            // Chemin source dans storage/app/public
            $sourcePath = storage_path('app/public/' . $path);
            
            // Chemin destination dans public/storage
            $destinationPath = public_path('storage/' . dirname($path));
            
            // Créer le répertoire de destination s'il n'existe pas
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            
            // Chemin complet de destination
            $fullDestinationPath = public_path('storage/' . $path);
            
            // Ne copier que si le fichier source existe et que la destination n'existe pas encore
            if (file_exists($sourcePath) && !file_exists($fullDestinationPath)) {
                // Copier le fichier
                copy($sourcePath, $fullDestinationPath);
                
                // Log pour débogage
                \Log::info("Fichier copié par MediaController: {$sourcePath} → {$fullDestinationPath}");
                return true;
            } 
            // Si le fichier source n'existe pas, chercher ailleurs
            else if (!file_exists($sourcePath)) {
                // Vérifier si le fichier existe ailleurs et essayer de le copier
                $alternativePaths = [
                    storage_path('app/media/' . $filename),
                    storage_path('app/public/media/' . $filename),
                    public_path('media/' . $filename)
                ];
                
                foreach ($alternativePaths as $altPath) {
                    if (file_exists($altPath) && !file_exists($fullDestinationPath)) {
                        copy($altPath, $fullDestinationPath);
                        \Log::info("Fichier copié d'un chemin alternatif par MediaController: {$altPath} → {$fullDestinationPath}");
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error("Erreur lors de la copie du fichier dans MediaController: " . $e->getMessage());
        }
        
        return false;
    }
}
