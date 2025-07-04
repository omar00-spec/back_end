<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

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
        
        // Pas besoin de formater les URLs car Cloudinary fournit des URLs complètes
        // Les anciennes entrées continueront d'utiliser formatMediaUrl

        return $media;
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,mp4,mov|max:20480',
            'title' => 'required',
            'type' => 'required|in:photo,video',
            'category_id' => 'nullable|exists:categories,id'
        ]);
        
        try {
            $file = $request->file('file');
            
            // Debug - Log des informations avant l'upload
            \Log::info('Tentative d\'upload sur Cloudinary', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType()
            ]);
            
            // Upload sur Cloudinary avec try/catch supplémentaire pour capturer l'erreur spécifique à Cloudinary
            try {
                $uploadedFileUrl = cloudinary()->upload($file->getRealPath(), [
                    'folder' => 'acos_football/' . $request->type . 's',
                    'resource_type' => 'auto'
                ])->getSecurePath();
                
                \Log::info('Upload Cloudinary réussi', ['url' => $uploadedFileUrl]);
            } catch (\Exception $cloudinaryError) {
                \Log::error('Erreur Cloudinary spécifique', [
                    'message' => $cloudinaryError->getMessage(),
                    'trace' => $cloudinaryError->getTraceAsString()
                ]);
                throw $cloudinaryError; // Relancer pour être capturé par le catch externe
            }
            
            // Créer l'entrée dans la base de données
            $media = new Media();
            $media->title = $request->title;
            $media->type = $request->type;
            $media->category_id = $request->category_id;
            $media->file_path = $uploadedFileUrl; // URL Cloudinary
            $media->save();
            
            \Log::info('Média sauvegardé en BDD', ['id' => $media->id, 'url' => $media->file_path]);
            
            return response()->json([
                'message' => 'Média téléchargé avec succès',
                'media' => $media
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du téléchargement du média', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erreur lors du téléchargement du média',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Media $media)
    {
        $media = $media->load('category');
        
        // Si ce n'est pas déjà une URL Cloudinary, formatez-la avec la méthode existante
        if (!$this->isCloudinaryUrl($media->file_path)) {
        $this->formatMediaUrl($media);
        }
        
        return $media;
    }

    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);
        
        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                
                // Debug - Log des informations avant l'update
                \Log::info('Tentative de mise à jour sur Cloudinary', [
                    'media_id' => $id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getMimeType()
                ]);
                
                // Si un nouveau fichier est téléchargé, supprimer l'ancien sur Cloudinary
                if ($this->isCloudinaryUrl($media->file_path)) {
                    $this->deleteFromCloudinary($media->file_path);
                }
                
                // Upload sur Cloudinary avec try/catch
                try {
                    $uploadedFileUrl = cloudinary()->upload($file->getRealPath(), [
                        'folder' => 'acos_football/' . ($request->type ?? $media->type) . 's',
                        'resource_type' => 'auto'
                    ])->getSecurePath();
                    
                    \Log::info('Mise à jour Cloudinary réussie', ['url' => $uploadedFileUrl]);
                    
                    // Mettre à jour le chemin
                    $media->file_path = $uploadedFileUrl;
                } catch (\Exception $cloudinaryError) {
                    \Log::error('Erreur Cloudinary lors de la mise à jour', [
                        'message' => $cloudinaryError->getMessage(),
                        'trace' => $cloudinaryError->getTraceAsString()
                    ]);
                    throw $cloudinaryError;
                }
            }
            
            // Mettre à jour les autres champs
            $media->title = $request->title ?? $media->title;
            $media->type = $request->type ?? $media->type;
            $media->category_id = $request->category_id ?? $media->category_id;
            $media->save();
            
            \Log::info('Média mis à jour en BDD', ['id' => $media->id, 'url' => $media->file_path]);

        return response()->json([
            'message' => 'Média mis à jour avec succès !',
            'media' => $media
        ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du média', [
                'media_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du média',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Media $media)
    {
        // Supprimer de Cloudinary si c'est une URL Cloudinary
        if ($this->isCloudinaryUrl($media->file_path)) {
            $this->deleteFromCloudinary($media->file_path);
        }
        
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
            
        // Formater les URLs des fichiers qui ne sont pas déjà sur Cloudinary
        foreach ($photos as $photo) {
            if (!$this->isCloudinaryUrl($photo->file_path)) {
            $this->formatMediaUrl($photo);
            }
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
            
        // Formater les URLs des fichiers qui ne sont pas déjà sur Cloudinary
        foreach ($videos as $video) {
            if (!$this->isCloudinaryUrl($video->file_path)) {
            $this->formatMediaUrl($video);
            }
        }

        return $videos;
    }

    /**
     * Vérifie si l'URL est une URL Cloudinary
     */
    private function isCloudinaryUrl($url)
    {
        return strpos($url, 'cloudinary.com') !== false;
    }

    /**
     * Supprime un fichier de Cloudinary
     */
    private function deleteFromCloudinary($url)
    {
        try {
            // Extraire l'ID public du fichier de l'URL
            $parts = parse_url($url);
            if (!isset($parts['path'])) {
                \Log::warning('URL Cloudinary invalide pour la suppression', ['url' => $url]);
                return false;
            }
            
            $path = $parts['path'];
            $pathParts = explode('/', $path);
            
            // Trouver les parties pertinentes (après /upload/)
            $uploadIndex = array_search('upload', $pathParts);
            if ($uploadIndex !== false) {
                // Construire le public_id en prenant tout après "upload"
                $publicId = implode('/', array_slice($pathParts, $uploadIndex + 2));
                
                // Enlever l'extension du fichier pour obtenir le public_id correct
                $publicId = pathinfo($publicId, PATHINFO_DIRNAME) . '/' . pathinfo($publicId, PATHINFO_FILENAME);
                $publicId = ltrim($publicId, '/'); // Enlever le slash initial s'il y en a un
                
                \Log::info('Tentative de suppression sur Cloudinary', ['public_id' => $publicId]);
                
                // Supprimer le fichier avec la bonne syntaxe
                $result = cloudinary()->destroy($publicId);
                
                \Log::info('Résultat de la suppression Cloudinary', ['result' => $result]);
                return $result;
            }
            
            \Log::warning('Impossible de trouver le segment "upload" dans l\'URL', ['url' => $url]);
            return false;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression du fichier Cloudinary', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $url
            ]);
            return false;
        }
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
            
        // Formater les URLs des fichiers qui ne sont pas déjà sur Cloudinary
        foreach ($media as $item) {
            if (!$this->isCloudinaryUrl($item->file_path)) {
            $this->formatMediaUrl($item);
            }
        }

        return $media;
    }
    
    /**
     * Formate l'URL du fichier média pour l'afficher correctement
     * (Conservé pour compatibilité avec les anciens fichiers)
     */
    public function formatMediaUrl($item)
    {
        if (!$item->file_path) {
            return;
        }
        
        // Si c'est déjà une URL Cloudinary, ne pas la modifier
        if ($this->isCloudinaryUrl($item->file_path)) {
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
    
    /**
     * Migrer les médias existants vers Cloudinary
     */
    public function migrateToCloudinary()
    {
        $media = Media::all();
        $count = 0;
        $errors = [];
        $successes = [];
        
        foreach ($media as $item) {
            // Ignorer les médias déjà sur Cloudinary
            if ($this->isCloudinaryUrl($item->file_path)) {
                continue;
            }
            
            // Ignorer les URLs externes comme YouTube
            if (filter_var($item->file_path, FILTER_VALIDATE_URL) && 
                (strpos($item->file_path, 'youtube') !== false || 
                strpos($item->file_path, 'vimeo') !== false)) {
                continue;
            }
            
            try {
                // Essayer de récupérer le fichier local
                $filePath = $item->file_path;
                $fileName = basename($filePath);
                
                \Log::info('Tentative de migration vers Cloudinary', [
                    'media_id' => $item->id, 
                    'file_path' => $filePath,
                    'file_name' => $fileName
                ]);
                
                $localPath = null;
                $possiblePaths = [
                    public_path('storage/media/' . $fileName),
                    storage_path('app/public/media/' . $fileName),
                    public_path('media/' . $fileName),
                    public_path($filePath)
                ];
                
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $localPath = $path;
                        \Log::info('Fichier trouvé à l\'emplacement: ' . $localPath);
                        break;
                    }
                }
                
                // Si le fichier local est trouvé, l'uploader sur Cloudinary
                if ($localPath) {
                    // Upload sur Cloudinary avec try/catch
                    try {
                        $uploadedFileUrl = cloudinary()->upload($localPath, [
                            'folder' => 'acos_football/' . $item->type . 's',
                            'resource_type' => 'auto'
                        ])->getSecurePath();
                        
                        // Mettre à jour l'entrée dans la base de données
                        $item->file_path = $uploadedFileUrl;
                        $item->save();
                        
                        $successes[] = "Média ID {$item->id}: {$fileName} migré avec succès";
                        \Log::info('Média migré avec succès', [
                            'media_id' => $item->id, 
                            'new_url' => $uploadedFileUrl
                        ]);
                        
                        $count++;
                    } catch (\Exception $cloudinaryError) {
                        $errors[] = "Erreur Cloudinary pour média ID {$item->id}: " . $cloudinaryError->getMessage();
                        \Log::error('Erreur lors de l\'upload Cloudinary pendant la migration', [
                            'media_id' => $item->id,
                            'error' => $cloudinaryError->getMessage()
                        ]);
                    }
                } else {
                    $errors[] = "Fichier non trouvé pour média ID {$item->id}: {$item->file_path}";
                    \Log::warning('Fichier non trouvé pour la migration', [
                        'media_id' => $item->id, 
                        'file_path' => $item->file_path
                    ]);
                }
            } catch (\Exception $e) {
                $errors[] = "Erreur pour média ID {$item->id}: " . $e->getMessage();
                \Log::error('Erreur générale lors de la migration', [
                    'media_id' => $item->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        \Log::info('Migration terminée', [
            'total_migrated' => $count,
            'total_errors' => count($errors)
        ]);
        
        return response()->json([
            'message' => "{$count} médias migrés vers Cloudinary",
            'successes' => $successes,
            'errors' => $errors
        ]);
    }
}
