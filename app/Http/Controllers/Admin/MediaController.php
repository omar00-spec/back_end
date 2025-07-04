<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Constructeur: vérifie et prépare les répertoires nécessaires
     */
    public function __construct()
    {
        $this->ensureDirectoriesExist();
    }
    
    /**
     * S'assure que les répertoires nécessaires existent
     */
    private function ensureDirectoriesExist()
    {
        try {
            // Vérifier/créer le répertoire storage/app/public/media
            $storageMediaPath = storage_path('app/public/media');
            if (!is_dir($storageMediaPath)) {
                if (!mkdir($storageMediaPath, 0777, true)) {
                    \Log::error("Impossible de créer le répertoire: {$storageMediaPath}");
                } else {
                    \Log::info("Répertoire créé: {$storageMediaPath}");
                }
            }
            
            // Vérifier/créer le répertoire public/storage/media
            $publicMediaPath = public_path('storage/media');
            if (!is_dir($publicMediaPath)) {
                if (!mkdir($publicMediaPath, 0777, true)) {
                    \Log::error("Impossible de créer le répertoire: {$publicMediaPath}");
                } else {
                    \Log::info("Répertoire créé: {$publicMediaPath}");
                }
            }
            
            // Vérifier les permissions
            if (is_dir($storageMediaPath)) {
                $perms = substr(sprintf('%o', fileperms($storageMediaPath)), -4);
                \Log::info("Permissions du répertoire storage/app/public/media: {$perms}");
                
                // Essayer de s'assurer que les permissions sont correctes
                chmod($storageMediaPath, 0777);
            }
            
            if (is_dir($publicMediaPath)) {
                $perms = substr(sprintf('%o', fileperms($publicMediaPath)), -4);
                \Log::info("Permissions du répertoire public/storage/media: {$perms}");
                
                // Essayer de s'assurer que les permissions sont correctes
                chmod($publicMediaPath, 0777);
            }
        } catch (\Exception $e) {
            \Log::error("Erreur lors de la vérification des répertoires: " . $e->getMessage());
        }
    }

    /**
     * Affiche la liste des médias (photos et vidéos)
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'all');

        if ($type === 'photo') {
            $items = Media::where('type', 'photo')->orderBy('created_at', 'desc')->get();
        } elseif ($type === 'video') {
            $items = Media::where('type', 'video')->orderBy('created_at', 'desc')->get();
        } else {
            $items = Media::orderBy('created_at', 'desc')->get();
        }

        return response()->json($items);
    }

    /**
     * Stocke un nouveau média
     */
    public function store(Request $request)
    {
        // Si un fichier est fourni, on l'upload
        if ($request->hasFile('file')) {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'category_id' => 'nullable|exists:categories,id',
                'type' => 'required|in:photo,video,image,document',
                'file' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,webm,mov,avi,3gp,mkv,flv,wmv|max:100000'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Créer le média d'abord sans fichier
            $media = Media::create([
                'title' => $request->title,
                'category_id' => $request->category_id,
                'type' => $request->type,
                'file_path' => null
            ]);

            // S'assurer que le répertoire media existe
            if (!Storage::disk('public')->exists('media')) {
                Storage::disk('public')->makeDirectory('media');
            }

            // Upload du fichier
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $filename = 'media_' . $media->id . '_' . time() . '.' . $extension;
            
            // Stocker le fichier dans storage/app/public/media
            $path = $file->storeAs('media', $filename, 'public');
            
            // Vérifier que le fichier a bien été stocké
            $sourcePath = storage_path('app/public/' . $path);
            if (!file_exists($sourcePath)) {
                \Log::error("Le fichier n'a pas été correctement stocké dans: {$sourcePath}");
                // Essayer de le stocker manuellement dans les deux emplacements
                $storageMediaPath = storage_path('app/public/media/' . $filename);
                $publicMediaPath = public_path('storage/media/' . $filename);
                
                try {
                    // Stocker directement dans storage/app/public/media
                    $file->move(storage_path('app/public/media'), $filename);
                    \Log::info("Fichier stocké manuellement dans: " . $storageMediaPath);
                    
                    // Et le copier également dans public/storage/media
                    if (file_exists($storageMediaPath)) {
                        if (copy($storageMediaPath, $publicMediaPath)) {
                            \Log::info("Fichier copié manuellement vers: " . $publicMediaPath);
                        } else {
                            // Si la copie a échoué, essayer de déplacer directement une seconde fois
                            $tmpFile = $file;
                            if ($tmpFile) {
                                $tmpFile->move(public_path('storage/media'), $filename);
                                \Log::info("Fichier stocké directement dans: " . $publicMediaPath);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error("Erreur lors du stockage manuel: " . $e->getMessage());
                }
            } else {
                \Log::info("Fichier correctement stocké dans: {$sourcePath}");
            }
            
            // Détecter le type de fichier si ce n'est pas explicitement spécifié
            $actualType = $request->type;
            
            // Si le type est auto ou non spécifié, le déterminer à partir du type MIME
            if (!$actualType || $actualType == 'auto') {
                $mimeType = $file->getMimeType();
                if (strpos($mimeType, 'image/') === 0) {
                    $actualType = 'image';
                } else if (strpos($mimeType, 'video/') === 0) {
                    $actualType = 'video';
                } else {
                    $actualType = 'document';
                }
                
                // Mettre à jour le type dans la base de données
                $media->type = $actualType;
            }

            // Détails du fichier pour debugging
            $fileInfo = [
                'originalName' => $file->getClientOriginalName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
                'extension' => $extension,
                'detectedType' => $actualType
            ];
            \Log::info('Informations sur le fichier téléchargé:', $fileInfo);

            // Mettre à jour le média avec le chemin du fichier
            $media->file_path = $path;
            $media->save();

            // S'assurer que le fichier est également copié vers public/storage/media
            $this->ensureCopyToPublicStorage($path, $filename);

            return response()->json([
                'success' => true,
                'media' => $media,
                'file_url' => asset('storage/' . $path),
                'file_info' => $fileInfo
            ], 201);
        }
        // Si un chemin personnalisé est fourni
        else if ($request->has('file_path')) {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'category_id' => 'nullable|exists:categories,id',
                'type' => 'required|in:photo,video',
                'file_path' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Normaliser le chemin ou l'URL
            $filePath = $request->file_path;
            
            // Si c'est une URL YouTube, s'assurer qu'elle est au format embed
            if (strpos($filePath, 'youtube.com/watch') !== false && strpos($filePath, 'embed') === false) {
                $query = parse_url($filePath, PHP_URL_QUERY);
                parse_str($query, $params);
                if (isset($params['v'])) {
                    $videoId = $params['v'];
                    $filePath = "https://www.youtube.com/embed/{$videoId}";
                }
            }
            
            // Si c'est une URL courte youtu.be, la convertir en format embed
            if (strpos($filePath, 'youtu.be/') !== false) {
                $parts = explode('/', $filePath);
                $videoId = end($parts);
                $filePath = "https://www.youtube.com/embed/{$videoId}";
            }
            
            // Si ce n'est pas une URL mais un chemin local
            if (!filter_var($filePath, FILTER_VALIDATE_URL)) {
                // Si le chemin ne commence pas par "media/", le préfixer
                if (strpos($filePath, 'media/') !== 0) {
                    // Si le chemin commence par un slash, le retirer
                    if (strpos($filePath, '/') === 0) {
                        $filePath = substr($filePath, 1);
                    }
                    
                    // Si le chemin ne commence toujours pas par "media/", le préfixer
                    if (strpos($filePath, 'media/') !== 0) {
                        $filePath = 'media/' . $filePath;
                    }
                }

                // S'assurer que le fichier est accessible dans public/storage
                $filename = basename($filePath);
                $this->ensureCopyToPublicStorage($filePath, $filename);
            }
            
            // Créer le média avec le chemin normalisé
            $media = Media::create([
                'title' => $request->title,
                'category_id' => $request->category_id,
                'type' => $request->type,
                'file_path' => $filePath
            ]);

            // Vérifier si le fichier existe réellement (seulement pour les fichiers locaux)
            $fileExists = filter_var($filePath, FILTER_VALIDATE_URL) || Storage::disk('public')->exists($filePath);

            return response()->json([
                'success' => true,
                'media' => $media,
                'file_url' => filter_var($filePath, FILTER_VALIDATE_URL) ? $filePath : asset('storage/' . $filePath),
                'file_exists' => $fileExists
            ], 201);
        }
        // Si ni fichier ni chemin n'est fourni
        else {
            return response()->json([
                'success' => false,
                'message' => 'Un fichier ou un chemin de fichier est requis'
            ], 422);
        }
    }

    /**
     * S'assure que le fichier est copié de storage/app/public/media vers public/storage/media
     */
    protected function ensureCopyToPublicStorage($path, $filename)
    {
        try {
            // Chemin source dans storage/app/public
            $sourcePath = storage_path('app/public/' . $path);
            
            // Log pour débogage
            \Log::info("Tentative de copie du fichier. Chemin source: {$sourcePath}");
            
            // Vérifier si le fichier source existe
            if (!file_exists($sourcePath)) {
                \Log::warning("Fichier source introuvable: {$sourcePath}");
                
                // Essayer de trouver le fichier dans d'autres emplacements possibles
                $alternativePaths = [
                    storage_path('app/media/' . $filename),
                    storage_path('app/public/media/' . $filename),
                    public_path('media/' . $filename),
                    storage_path('app/' . $path)
                ];
                
                foreach ($alternativePaths as $altPath) {
                    \Log::info("Vérification du chemin alternatif: {$altPath}");
                    if (file_exists($altPath)) {
                        \Log::info("Fichier trouvé dans un emplacement alternatif: {$altPath}");
                        $sourcePath = $altPath;
                        break;
                    }
                }
                
                // Si après recherche, le fichier n'est toujours pas trouvé
                if (!file_exists($sourcePath)) {
                    \Log::error("Impossible de trouver le fichier à copier.");
                    return false;
                }
            }
            
            // Chemin destination dans public/storage
            $destinationDir = public_path('storage/' . dirname($path));
            $fullDestinationPath = public_path('storage/' . $path);
            
            \Log::info("Destination finale: {$fullDestinationPath}");
            
            // Vérifier si le fichier de destination existe déjà
            if (file_exists($fullDestinationPath)) {
                \Log::info("Le fichier de destination existe déjà: {$fullDestinationPath}");
                return true; // Le fichier est déjà copié
            }
            
            // Créer le répertoire de destination s'il n'existe pas
            if (!is_dir($destinationDir)) {
                \Log::info("Création du répertoire de destination: {$destinationDir}");
                
                // Utiliser mkdir avec récursion
                if (!mkdir($destinationDir, 0777, true)) {
                    \Log::error("Échec de la création du répertoire: {$destinationDir}. Vérification des permissions...");
                    
                    // Vérifier les permissions du répertoire parent
                    $parentDir = dirname($destinationDir);
                    if (file_exists($parentDir)) {
                        $perms = substr(sprintf('%o', fileperms($parentDir)), -4);
                        \Log::info("Permissions du répertoire parent {$parentDir}: {$perms}");
                    }
                    
                    return false;
                }
            }
            
            // Vérifier les permissions du répertoire de destination
            $perms = substr(sprintf('%o', fileperms($destinationDir)), -4);
            \Log::info("Permissions du répertoire de destination {$destinationDir}: {$perms}");
            
            // Essayer de copier avec copy() d'abord
            $copyResult = copy($sourcePath, $fullDestinationPath);
            
            if ($copyResult) {
                \Log::info("Fichier copié avec succès: {$sourcePath} → {$fullDestinationPath}");
                
                // Vérifier que le fichier existe après la copie
                if (file_exists($fullDestinationPath)) {
                    \Log::info("Vérification réussie: le fichier existe à destination.");
                    return true;
                } else {
                    \Log::warning("Étrange: la copie a réussi mais le fichier n'existe pas à destination.");
                }
            } else {
                \Log::error("Échec de la copie avec copy(). Tentative avec file_put_contents()...");
                
                // Essayer avec file_put_contents si copy() a échoué
                $fileContent = file_get_contents($sourcePath);
                if ($fileContent !== false) {
                    $putResult = file_put_contents($fullDestinationPath, $fileContent);
                    if ($putResult !== false) {
                        \Log::info("Fichier copié avec succès via file_put_contents: {$sourcePath} → {$fullDestinationPath}");
                        return true;
                    } else {
                        \Log::error("Échec de la copie avec file_put_contents. Problème possible de permissions.");
                    }
                } else {
                    \Log::error("Impossible de lire le contenu du fichier source: {$sourcePath}");
                }
            }
            
            return false;
        } catch (\Exception $e) {
            \Log::error("Exception lors de la copie du fichier: " . $e->getMessage());
            \Log::error("Trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Affiche un média spécifique
     */
    public function show($id)
    {
        $media = Media::findOrFail($id);
        return response()->json($media);
    }

    /**
     * Met à jour un média existant
     */
    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        // Si un fichier est fourni, l'uploader
        if ($request->hasFile('file')) {
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'category_id' => 'nullable|exists:categories,id',
                'type' => 'sometimes|required|in:photo,video,image,document',
                'file' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,webm,mov,avi,3gp,mkv,flv,wmv|max:100000'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // S'assurer que le répertoire media existe
            if (!Storage::disk('public')->exists('media')) {
                Storage::disk('public')->makeDirectory('media');
            }

            // Upload du nouveau fichier
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $filename = 'media_' . $media->id . '_' . time() . '.' . $extension;
            $path = $file->storeAs('media', $filename, 'public');

            // Détecter le type de fichier si ce n'est pas explicitement spécifié
            $actualType = $request->type ?? $media->type;
            
            // Si le type est auto ou non spécifié, le déterminer à partir du type MIME
            if (!$actualType || $actualType == 'auto') {
                $mimeType = $file->getMimeType();
                if (strpos($mimeType, 'image/') === 0) {
                    $actualType = 'image';
                } else if (strpos($mimeType, 'video/') === 0) {
                    $actualType = 'video';
                } else {
                    $actualType = 'document';
                }
            }
            
            // Mettre à jour le type
            $media->type = $actualType;

            // Détails du fichier pour debugging
            $fileInfo = [
                'originalName' => $file->getClientOriginalName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
                'extension' => $extension,
                'detectedType' => $actualType
            ];
            \Log::info('Informations sur le fichier mis à jour:', $fileInfo);

            // Mettre à jour le média avec le nouveau chemin du fichier
            $media->file_path = $path;

            // S'assurer que le fichier est copié vers public/storage/media
            $this->ensureCopyToPublicStorage($path, $filename);
        }
        // Si un chemin est fourni mais pas de fichier
        else if ($request->has('file_path')) {
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'category_id' => 'nullable|exists:categories,id',
                'type' => 'sometimes|required|in:photo,video',
                'file_path' => 'sometimes|required|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Normaliser le chemin ou l'URL
            if ($request->has('file_path')) {
                $filePath = $request->file_path;
                
                // Si c'est une URL YouTube, s'assurer qu'elle est au format embed
                if (strpos($filePath, 'youtube.com/watch') !== false && strpos($filePath, 'embed') === false) {
                    $query = parse_url($filePath, PHP_URL_QUERY);
                    parse_str($query, $params);
                    if (isset($params['v'])) {
                        $videoId = $params['v'];
                        $filePath = "https://www.youtube.com/embed/{$videoId}";
                    }
                }
                
                // Si c'est une URL courte youtu.be, la convertir en format embed
                if (strpos($filePath, 'youtu.be/') !== false) {
                    $parts = explode('/', $filePath);
                    $videoId = end($parts);
                    $filePath = "https://www.youtube.com/embed/{$videoId}";
                }
                
                // Si ce n'est pas une URL mais un chemin local
                if (!filter_var($filePath, FILTER_VALIDATE_URL)) {
                    // Si le chemin ne commence pas par "media/", le préfixer
                    if (strpos($filePath, 'media/') !== 0) {
                        // Si le chemin commence par un slash, le retirer
                        if (strpos($filePath, '/') === 0) {
                            $filePath = substr($filePath, 1);
                        }
                        
                        // Si le chemin ne commence toujours pas par "media/", le préfixer
                        if (strpos($filePath, 'media/') !== 0) {
                            $filePath = 'media/' . $filePath;
                        }
                    }
                    
                    // S'assurer que le fichier est accessible dans public/storage
                    $filename = basename($filePath);
                    $this->ensureCopyToPublicStorage($filePath, $filename);
                }
                
                $media->file_path = $filePath;
            }
        }

        // Mettre à jour les autres champs
        if ($request->has('title')) {
            $media->title = $request->title;
        }
        
        if ($request->has('category_id')) {
            $media->category_id = $request->category_id;
        }
        
        if ($request->has('type')) {
            $media->type = $request->type;
        }
        
        $media->save();

        return response()->json($media);
    }

    /**
     * Supprime un média
     */
    public function destroy($id)
    {
        $media = Media::findOrFail($id);
        
        // Récupérer le chemin du fichier avant de supprimer l'enregistrement
        $filePath = $media->file_path;
        
        // Supprimer d'abord l'enregistrement de la base de données
        $media->delete();
        
        // Si le chemin du fichier existe, essayer de supprimer le fichier du stockage
        if ($filePath) {
            try {
                // Vérifier si c'est une URL (externe) ou un chemin local
                if (filter_var($filePath, FILTER_VALIDATE_URL)) {
                    // Si c'est une URL externe (YouTube, etc.), on ne supprime pas de fichier
                    \Log::info("URL externe non supprimée: {$filePath}");
                } else {
                    // Si c'est un chemin local, normaliser le chemin
                    $normalizedPath = $filePath;
                    
                    // Si le chemin commence par 'media/', supprimer le préfixe car Storage::disk('public') pointe déjà vers ce répertoire
                    if (strpos($normalizedPath, 'media/') === 0) {
                        $normalizedPath = substr($normalizedPath, 6);
                    }
                    
                    // Essayer de supprimer du stockage public
                    if (Storage::disk('public')->exists($normalizedPath)) {
                        Storage::disk('public')->delete($normalizedPath);
                        \Log::info("Fichier supprimé du stockage public: {$normalizedPath}");
                    } else if (Storage::disk('public')->exists('media/' . $normalizedPath)) {
                        Storage::disk('public')->delete('media/' . $normalizedPath);
                        \Log::info("Fichier supprimé du stockage public (avec préfixe media): media/{$normalizedPath}");
                    } else {
                        \Log::warning("Fichier introuvable dans le stockage public: {$normalizedPath}");
                        
                        // Essayer de supprimer du répertoire public/storage/media directement
                        $publicMediaPath = public_path('storage/media/' . basename($filePath));
                        if (file_exists($publicMediaPath)) {
                            unlink($publicMediaPath);
                            \Log::info("Fichier supprimé directement du répertoire public: {$publicMediaPath}");
                        } else {
                            \Log::warning("Fichier introuvable dans le répertoire public: {$publicMediaPath}");
                        }
                    }
                }
            } catch (\Exception $e) {
                // Enregistrer l'erreur mais ne pas échouer la suppression si le fichier ne peut pas être supprimé
                \Log::error("Erreur lors de la suppression du fichier: " . $e->getMessage());
            }
        }

        return response()->json(null, 204);
    }
}
