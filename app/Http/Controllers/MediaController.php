<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        \Log::info('MediaController::index appelé', [
            'request_params' => $request->all(),
            'request_path' => $request->path(),
            'request_url' => $request->url(),
        ]);
        
        $query = Media::query()->with('category');

        // Filtrer par type si spécifié
        if ($request->has('type')) {
            $query->where('type', $request->type);
            \Log::info('Filtrage par type', ['type' => $request->type]);
        }

        // Filtrer par catégorie si spécifié
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
            \Log::info('Filtrage par catégorie', ['category_id' => $request->category_id]);
        }

        // Trier par date de création
        $query->orderBy('created_at', 'desc');

        $media = $query->get();
        
        \Log::info('Médias récupérés', [
            'count' => $media->count(),
            'first_media' => $media->first() ? [
                'id' => $media->first()->id,
                'title' => $media->first()->title,
                'type' => $media->first()->type,
                'file_path' => $media->first()->file_path,
            ] : null
        ]);
        
        // Pas besoin de formater les URLs car Cloudinary fournit des URLs complètes
        // Les anciennes entrées continueront d'utiliser formatMediaUrl

        return $media;
    }

    public function store(Request $request)
    {
        \Log::info('Début de la méthode store de MediaController', [
            'request_all' => $request->all(),
            'request_has_file' => $request->hasFile('file'),
            'files' => $request->allFiles()
        ]);
        
        // Validation des données avec des règles plus permissives pour les vidéos
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:photo,video,document',
            'category_id' => 'nullable|exists:categories,id',
            'file' => 'required|file|max:102400', // 100MB max pour permettre les vidéos
        ]);

        if ($validator->fails()) {
            \Log::error('Validation échouée pour le média', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Récupérer le fichier
            $file = $request->file('file');
            
            // Vérification détaillée du fichier
            if (!$file || !$file->isValid()) {
                \Log::error('Fichier invalide ou non reçu', [
                    'file_exists' => $request->hasFile('file'),
                    'all_files' => $request->allFiles(),
                    'request_data' => $request->all()
                ]);
                return response()->json([
                    'message' => 'Le fichier est invalide ou n\'a pas été reçu',
                    'error' => 'file_invalid'
                ], 400);
            }
            
            // Debug - Log des informations avant l'upload
            \Log::info('Tentative d\'upload sur Cloudinary', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'requested_type' => $request->type,
                'cloudinary_config' => [
                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME') ? 'Défini' : 'Non défini',
                    'api_key' => env('CLOUDINARY_KEY') ? 'Défini' : 'Non défini',
                    'api_secret' => env('CLOUDINARY_SECRET') ? 'Défini' : 'Non défini',
                    'url' => env('CLOUDINARY_URL') ? 'Défini' : 'Non défini',
                ]
            ]);
            
            // Upload sur Cloudinary - sans fallback
            $folder = 'acos_football/' . $request->type . 's';
            \Log::info("Dossier cible sur Cloudinary: {$folder}");
            
            try {
                // Obtenir le chemin réel du fichier pour l'upload
                $filePath = $file->getRealPath();
                \Log::info("Chemin réel du fichier: {$filePath}");
                
                // Vérification supplémentaire du fichier
                if (!file_exists($filePath)) {
                    \Log::error("Le fichier n'existe pas au chemin spécifié: {$filePath}");
                    return response()->json([
                        'message' => 'Le fichier n\'existe pas au chemin spécifié',
                        'error' => 'file_not_found'
                    ], 400);
                }
                
                // Tentative d'upload sur Cloudinary avec configuration explicite
                $cloudName = env('CLOUDINARY_CLOUD_NAME');
                $apiKey = env('CLOUDINARY_KEY');
                $apiSecret = env('CLOUDINARY_SECRET');
                
                if (!$cloudName || !$apiKey || !$apiSecret) {
                    \Log::error("Configuration Cloudinary incomplète", [
                        'cloud_name' => $cloudName ? 'Défini' : 'Non défini',
                        'api_key' => $apiKey ? 'Défini' : 'Non défini',
                        'api_secret' => $apiSecret ? 'Défini' : 'Non défini'
                    ]);
                    
                    // Fallback vers le stockage local si Cloudinary n'est pas configuré
                    \Log::info("Tentative de fallback vers stockage local");
                    
                    // Générer un nom de fichier unique
                    $filename = 'media_' . time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('public/media', $filename);
                    $uploadedFileUrl = asset('storage/media/' . $filename);
                    
                    \Log::info("Fichier sauvegardé localement", [
                        'path' => $path,
                        'url' => $uploadedFileUrl
                    ]);
                } else {
                    \Log::info("Configuration Cloudinary explicite", [
                        'cloud_name' => $cloudName,
                        'api_key' => substr($apiKey, 0, 3) . '...' // Ne log pas la clé complète
                    ]);
                    
                    // Utiliser l'instance Cloudinary directement
                    $config = [
                        'cloud' => [
                            'cloud_name' => $cloudName,
                            'api_key' => $apiKey,
                            'api_secret' => $apiSecret
                        ]
                    ];
                    
                    // Initialiser une variable pour l'URL du fichier uploadé
                    $uploadedFileUrl = null;
                    
                    // Déterminer le type de ressource pour Cloudinary
                    $resourceType = 'auto';
                    if ($request->type === 'video') {
                        $resourceType = 'video';
                        
                        // Liste des extensions vidéo supportées
                        $supportedVideoExtensions = [
                            'mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv', 'm4v', '3gp', 'mpeg', 'mpg'
                        ];
                        
                        // Vérifier si l'extension est supportée
                        $extension = strtolower($file->getClientOriginalExtension());
                        if (!in_array($extension, $supportedVideoExtensions)) {
                            \Log::warning("Extension vidéo potentiellement non supportée: {$extension}", [
                                'file_name' => $file->getClientOriginalName(),
                                'mime_type' => $file->getMimeType()
                            ]);
                        }
                        
                        // Log détaillé pour les vidéos
                        \Log::info("Traitement d'une vidéo", [
                            'extension' => $extension,
                            'mime_type' => $file->getMimeType(),
                            'size' => $file->getSize()
                        ]);
                    } elseif ($request->type === 'photo') {
                        $resourceType = 'image';
                    }
                    
                    // Utiliser le SDK directement
                    if (class_exists('Cloudinary\Cloudinary')) {
                        $cloudinary = new \Cloudinary\Cloudinary($config);
                        $uploadApi = $cloudinary->uploadApi();
                        
                        \Log::info("Tentative d'upload avec le SDK Cloudinary", [
                            'resource_type' => $resourceType,
                            'folder' => $folder
                        ]);
                        
                        try {
                            // Options d'upload de base
                            $uploadOptions = [
                                'folder' => $folder,
                                'resource_type' => $resourceType
                            ];
                            
                            // Options spécifiques pour les vidéos
                            if ($request->type === 'video') {
                                // Ajouter des options pour optimiser le traitement des vidéos
                                $uploadOptions['chunk_size'] = 6000000; // 6MB chunks pour les vidéos volumineuses
                                $uploadOptions['timeout'] = 120; // Timeout plus long pour les vidéos
                                
                                // Essayer de détecter le codec et le format
                                $mimeType = $file->getMimeType();
                                \Log::info("Type MIME de la vidéo: {$mimeType}");
                                
                                // Formats qui peuvent nécessiter une conversion
                                if (strpos($mimeType, 'quicktime') !== false || 
                                    strpos($mimeType, 'x-msvideo') !== false || 
                                    in_array($file->getClientOriginalExtension(), ['mov', 'avi', 'wmv'])) {
                                    \Log::info("Format vidéo qui pourrait nécessiter une conversion: {$mimeType}");
                                    // Laisser Cloudinary gérer la conversion
                                }
                            }
                            
                            $uploadResult = $uploadApi->upload($filePath, $uploadOptions);
                            
                            $uploadedFileUrl = $uploadResult['secure_url'];
                            \Log::info("Upload réussi avec SDK Cloudinary", ['url' => $uploadedFileUrl]);
                        } catch (\Exception $cloudinaryError) {
                            \Log::error("Erreur lors de l'upload avec le SDK Cloudinary", [
                                'message' => $cloudinaryError->getMessage(),
                                'trace' => $cloudinaryError->getTraceAsString()
                            ]);
                            throw $cloudinaryError;
                        }
                    } 
                    // Fallback vers la façade Laravel si disponible
                    else if (class_exists('CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary')) {
                        // Upload avec l'instance configurée explicitement
                        \Log::info("Tentative d'upload avec la façade Cloudinary Laravel", [
                            'resource_type' => $resourceType,
                            'folder' => $folder
                        ]);
                        
                        try {
                            $uploadResult = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::upload($filePath, [
                                'folder' => $folder,
                                'resource_type' => $resourceType
                            ]);
                            
                            $uploadedFileUrl = $uploadResult->getSecurePath();
                            \Log::info("Upload réussi avec façade Cloudinary Laravel", ['url' => $uploadedFileUrl]);
                        } catch (\Exception $cloudinaryError) {
                            \Log::error("Erreur lors de l'upload avec la façade Cloudinary Laravel", [
                                'message' => $cloudinaryError->getMessage(),
                                'trace' => $cloudinaryError->getTraceAsString()
                            ]);
                            throw $cloudinaryError;
                        }
                    } else {
                        throw new \Exception("Aucun SDK Cloudinary disponible");
                    }
                }
                
                // Vérifier que l'URL a bien été obtenue
                if (empty($uploadedFileUrl)) {
                    \Log::warning("L'URL du fichier uploadé est vide, mais on continue avec un champ null");
                }
                
                $media = new Media();
                $media->title = $request->title;
                $media->type = $request->type;
                $media->category_id = $request->category_id;
                $media->file_path = $uploadedFileUrl ?? null; // Permettre une valeur null
                
                // Log avant sauvegarde
                \Log::info('Tentative de sauvegarde média en BDD', [
                    'title' => $media->title,
                    'type' => $media->type,
                    'category_id' => $media->category_id,
                    'file_path' => $media->file_path ?? 'NULL'
                ]);
                
                $media->save();
                
                \Log::info('Média sauvegardé en BDD', [
                    'id' => $media->id, 
                    'url' => $media->file_path
                ]);
                
                // Retourner une réponse formatée avec le média créé
                return response()->json([
                    'message' => 'Média ajouté avec succès',
                    'media' => $media
                ], 201);
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la création du média', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'message' => 'Erreur lors de la création du média',
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Erreur globale lors de la création du média', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erreur globale lors de la création du média',
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
        \Log::info('Début de la méthode update de MediaController', [
            'id' => $id,
            'request_all' => $request->all(),
            'request_has_file' => $request->hasFile('file'),
            'files' => $request->allFiles()
        ]);
        
        try {
            $media = Media::findOrFail($id);
            
            // Log détaillé de la requête reçue
            \Log::info('Requête de mise à jour de média reçue', [
                'media_id' => $id,
                'request_has_file' => $request->hasFile('file'),
                'request_all' => $request->all(),
                'request_files' => $request->allFiles()
            ]);
            
            try {
                $uploadedFileUrl = null;
                
                if ($request->hasFile('file')) {
                    $file = $request->file('file');
                    
                    // Vérification approfondie du fichier
                    if (!$file->isValid()) {
                        \Log::error('Fichier invalide lors de la mise à jour', [
                            'media_id' => $id,
                            'file_info' => [
                                'name' => $file->getClientOriginalName(),
                                'size' => $file->getSize(),
                                'error' => $file->getError()
                            ]
                        ]);
                        return response()->json([
                            'message' => 'Le fichier téléchargé est invalide',
                            'error' => 'file_invalid'
                        ], 400);
                    }
                    
                    // Debug - Log des informations avant l'update
                    \Log::info('Tentative de mise à jour sur Cloudinary', [
                        'media_id' => $id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'file_type' => $file->getMimeType(),
                        'media_type' => $media->type,
                        'cloudinary_config' => [
                            'cloud_name' => env('CLOUDINARY_CLOUD_NAME') ? 'Défini' : 'Non défini',
                            'api_key' => env('CLOUDINARY_KEY') ? 'Défini' : 'Non défini',
                            'api_secret' => env('CLOUDINARY_SECRET') ? 'Défini' : 'Non défini',
                            'url' => env('CLOUDINARY_URL') ? 'Défini' : 'Non défini',
                        ]
                    ]);
                    
                    // Si un nouveau fichier est téléchargé, supprimer l'ancien sur Cloudinary
                    if ($this->isCloudinaryUrl($media->file_path)) {
                        $this->deleteFromCloudinary($media->file_path);
                    }
                    
                    // Upload sur Cloudinary - sans fallback
                    $folder = 'acos_football/' . ($request->type ?? $media->type) . 's';
                    \Log::info("Dossier cible sur Cloudinary: {$folder}");
                    
                    try {
                        // Obtenir le chemin réel du fichier pour l'upload
                        $filePath = $file->getRealPath();
                        \Log::info("Chemin réel du fichier: {$filePath}");
                        
                        // Vérification supplémentaire du fichier
                        if (!file_exists($filePath)) {
                            \Log::error("Le fichier n'existe pas au chemin spécifié: {$filePath}");
                            return response()->json([
                                'message' => 'Le fichier n\'existe pas au chemin spécifié',
                                'error' => 'file_not_found'
                            ], 400);
                        }
                        
                        // Vérifier la configuration Cloudinary
                        $cloudName = env('CLOUDINARY_CLOUD_NAME');
                        $apiKey = env('CLOUDINARY_KEY');
                        $apiSecret = env('CLOUDINARY_SECRET');
                        
                        if (!$cloudName || !$apiKey || !$apiSecret) {
                            \Log::error("Configuration Cloudinary incomplète", [
                                'cloud_name' => $cloudName ? 'Défini' : 'Non défini',
                                'api_key' => $apiKey ? 'Défini' : 'Non défini',
                                'api_secret' => $apiSecret ? 'Défini' : 'Non défini'
                            ]);
                            
                            // Fallback vers le stockage local si Cloudinary n'est pas configuré
                            \Log::info("Tentative de fallback vers stockage local");
                            
                            // Générer un nom de fichier unique
                            $filename = 'media_' . time() . '_' . $file->getClientOriginalName();
                            $path = $file->storeAs('public/media', $filename);
                            $uploadedFileUrl = asset('storage/media/' . $filename);
                            
                            \Log::info("Fichier sauvegardé localement", [
                                'path' => $path,
                                'url' => $uploadedFileUrl
                            ]);
                        } else {
                        // Utiliser l'instance Cloudinary directement
                        $config = [
                            'cloud' => [
                                'cloud_name' => $cloudName,
                                'api_key' => $apiKey,
                                'api_secret' => $apiSecret
                            ]
                        ];
                            
                            // Déterminer le type de ressource pour Cloudinary
                            $resourceType = 'auto';
                            $mediaType = $request->type ?? $media->type;
                            if ($mediaType === 'video') {
                                $resourceType = 'video';
                                
                                // Liste des extensions vidéo supportées
                                $supportedVideoExtensions = [
                                    'mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv', 'm4v', '3gp', 'mpeg', 'mpg'
                                ];
                                
                                // Vérifier si l'extension est supportée
                                $extension = strtolower($file->getClientOriginalExtension());
                                if (!in_array($extension, $supportedVideoExtensions)) {
                                    \Log::warning("Extension vidéo potentiellement non supportée lors de la mise à jour: {$extension}", [
                                        'file_name' => $file->getClientOriginalName(),
                                        'mime_type' => $file->getMimeType()
                                    ]);
                                }
                                
                                // Log détaillé pour les vidéos
                                \Log::info("Traitement d'une vidéo lors de la mise à jour", [
                                    'extension' => $extension,
                                    'mime_type' => $file->getMimeType(),
                                    'size' => $file->getSize()
                                ]);
                            } elseif ($mediaType === 'photo') {
                                $resourceType = 'image';
                            }
                            
                            \Log::info("Type de ressource pour l'upload: {$resourceType}");
                        
                        // Utiliser le SDK directement
                        if (class_exists('Cloudinary\Cloudinary')) {
                            $cloudinary = new \Cloudinary\Cloudinary($config);
                            $uploadApi = $cloudinary->uploadApi();
                                
                                \Log::info("Tentative d'upload avec le SDK Cloudinary", [
                                    'resource_type' => $resourceType,
                                    'folder' => $folder
                                ]);
                                
                            // Options d'upload spécifiques selon le type
                            $uploadOptions = [
                                'folder' => $folder,
                                'resource_type' => $resourceType
                            ];
                            
                            // Options spécifiques pour les vidéos
                            if ($mediaType === 'video') {
                                // Ajouter des options pour optimiser le traitement des vidéos
                                $uploadOptions['chunk_size'] = 6000000; // 6MB chunks pour les vidéos volumineuses
                                $uploadOptions['timeout'] = 120; // Timeout plus long pour les vidéos
                                
                                // Essayer de détecter le codec et le format
                                $mimeType = $file->getMimeType();
                                \Log::info("Type MIME de la vidéo lors de la mise à jour: {$mimeType}");
                                
                                // Formats qui peuvent nécessiter une conversion
                                if (strpos($mimeType, 'quicktime') !== false || 
                                    strpos($mimeType, 'x-msvideo') !== false || 
                                    in_array($file->getClientOriginalExtension(), ['mov', 'avi', 'wmv'])) {
                                    \Log::info("Format vidéo qui pourrait nécessiter une conversion lors de la mise à jour: {$mimeType}");
                                    // Laisser Cloudinary gérer la conversion
                                }
                            }
                            
                            $uploadResult = $uploadApi->upload($filePath, $uploadOptions);
                            
                            $uploadedFileUrl = $uploadResult['secure_url'];
                                \Log::info("Upload réussi avec SDK Cloudinary", ['url' => $uploadedFileUrl]);
                        } 
                        // Fallback vers la façade Laravel si disponible
                        else if (class_exists('CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary')) {
                            // Upload avec l'instance configurée explicitement
                                \Log::info("Tentative d'upload avec la façade Cloudinary Laravel", [
                                    'resource_type' => $resourceType,
                                    'folder' => $folder
                                ]);
                                
                            $uploadResult = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::upload($filePath, [
                                'folder' => $folder,
                                    'resource_type' => $resourceType
                            ]);
                            
                            $uploadedFileUrl = $uploadResult->getSecurePath();
                                \Log::info("Upload réussi avec façade Cloudinary Laravel", ['url' => $uploadedFileUrl]);
                        } else {
                            throw new \Exception("Aucun SDK Cloudinary disponible");
                            }
                        }
                        
                        // Vérifier que l'URL a bien été obtenue
                        if (empty($uploadedFileUrl)) {
                            throw new \Exception("L'URL du fichier uploadé est vide");
                        }
                        
                        // Mettre à jour le chemin
                        $media->file_path = $uploadedFileUrl;
                    } catch (\Exception $cloudinaryError) {
                        // Log détaillé de l'erreur Cloudinary
                        \Log::error('Erreur Cloudinary détaillée lors de la mise à jour', [
                            'message' => $cloudinaryError->getMessage(),
                            'trace' => $cloudinaryError->getTraceAsString(),
                            'code' => $cloudinaryError->getCode(),
                            'file' => $cloudinaryError->getFile(),
                            'line' => $cloudinaryError->getLine()
                        ]);
                        
                        // Pas de fallback, retourner l'erreur directement
                        return response()->json([
                            'message' => 'Erreur lors de l\'upload sur Cloudinary',
                            'error' => $cloudinaryError->getMessage()
                        ], 500);
                    }
                }
                
                // Mettre à jour les autres champs
                $media->title = $request->title ?? $media->title;
                $media->type = $request->type ?? $media->type;
                $media->category_id = $request->category_id ?? $media->category_id;
                
                // Log avant sauvegarde
                \Log::info('Tentative de sauvegarde média mis à jour en BDD', [
                    'title' => $media->title,
                    'type' => $media->type,
                    'category_id' => $media->category_id,
                    'file_path' => $media->file_path
                ]);
                
                $media->save();
                
                \Log::info('Média mis à jour en BDD', [
                    'id' => $media->id, 
                    'url' => $media->file_path,
                    'type' => $media->type,
                ]);
                
                // Retourner une réponse formatée avec le média mis à jour
                return response()->json([
                    'message' => 'Média mis à jour avec succès',
                    'media' => $media
                ]);
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la mise à jour du média', [
                    'id' => $id,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'message' => 'Erreur lors de la mise à jour du média',
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la recherche du média à mettre à jour', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erreur lors de la recherche du média',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function destroy(Media $media)
    {
        \Log::info('Tentative de suppression du média', [
            'id' => $media->id,
            'type' => $media->type,
            'file_path' => $media->file_path
        ]);
        
        try {
            // Si le fichier est stocké sur Cloudinary, le supprimer de Cloudinary
            if ($this->isCloudinaryUrl($media->file_path)) {
                $result = $this->deleteFromCloudinary($media->file_path);
                \Log::info('Résultat de la suppression Cloudinary', $result);
            } else {
                // Si le fichier est stocké localement, le supprimer du stockage local
                $filePath = $this->getLocalPathFromUrl($media->file_path);
                if ($filePath && \Storage::exists($filePath)) {
                    \Storage::delete($filePath);
                    \Log::info('Fichier local supprimé', ['path' => $filePath]);
                } else {
                    \Log::warning('Fichier local non trouvé', ['path' => $filePath]);
                }
            }
            
            // Supprimer l'entrée de la base de données
            $media->delete();
            \Log::info('Média supprimé de la base de données', ['id' => $media->id]);
            
            return response()->json(['message' => 'Média supprimé avec succès']);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression du média', [
                'id' => $media->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erreur lors de la suppression du média',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Extraire le chemin local à partir d'une URL
     */
    private function getLocalPathFromUrl($url)
    {
        // Si l'URL est vide ou null, retourner null
        if (empty($url)) {
            return null;
        }
        
        // Si c'est une URL complète
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            // Extraire le chemin relatif de l'URL
            $parsedUrl = parse_url($url);
            if (isset($parsedUrl['path'])) {
                $path = $parsedUrl['path'];
                // Chercher "storage/" dans le chemin
                $storagePos = strpos($path, 'storage/');
                if ($storagePos !== false) {
                    $relativePath = substr($path, $storagePos);
                    return 'public/' . substr($relativePath, 8); // Convertir "storage/xxx" en "public/xxx"
                }
            }
        } else {
            // Si c'est un chemin relatif
            if (strpos($url, 'storage/') === 0) {
                return 'public/' . substr($url, 8); // Convertir "storage/xxx" en "public/xxx"
            }
        }
        
        return null;
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
     * Supprimer un fichier de Cloudinary
     */
    private function deleteFromCloudinary($url)
    {
        \Log::info('Tentative de suppression sur Cloudinary', ['url' => $url]);
        
        // Si l'URL est vide, retourner
        if (empty($url)) {
            return ['status' => 'error', 'message' => 'URL vide'];
        }
        
        try {
            // Extraire l'ID public de l'URL Cloudinary
            $publicId = $this->extractPublicIdFromUrl($url);
            
            if (!$publicId) {
                return ['status' => 'error', 'message' => 'Impossible d\'extraire l\'ID public de l\'URL'];
            }
            
            \Log::info('ID public extrait', ['public_id' => $publicId]);
            
            // Déterminer le type de ressource (image ou vidéo)
            $resourceType = 'image';
            if (strpos($url, '/video/') !== false) {
                $resourceType = 'video';
                \Log::info('Ressource détectée comme vidéo');
            }
            
            // Récupérer la configuration Cloudinary
            $cloudName = env('CLOUDINARY_CLOUD_NAME');
            $apiKey = env('CLOUDINARY_KEY');
            $apiSecret = env('CLOUDINARY_SECRET');
            
            if (!$cloudName || !$apiKey || !$apiSecret) {
                \Log::error('Configuration Cloudinary incomplète');
                return ['status' => 'error', 'message' => 'Configuration Cloudinary incomplète'];
            }
            
            // Utiliser le SDK Cloudinary pour supprimer le fichier
            $config = [
                'cloud' => [
                    'cloud_name' => $cloudName,
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret
                ]
            ];
            
            if (class_exists('Cloudinary\Cloudinary')) {
                $cloudinary = new \Cloudinary\Cloudinary($config);
                $uploadApi = $cloudinary->uploadApi();
                
                \Log::info('Tentative de suppression avec le SDK Cloudinary', [
                    'public_id' => $publicId,
                    'resource_type' => $resourceType
                ]);
                
                $result = $uploadApi->destroy($publicId, [
                    'resource_type' => $resourceType
                ]);
                
                \Log::info('Résultat de la suppression Cloudinary', $result);
                return $result;
            } elseif (class_exists('CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary')) {
                \Log::info('Tentative de suppression avec la façade Cloudinary Laravel', [
                    'public_id' => $publicId,
                    'resource_type' => $resourceType
                ]);
                
                $result = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::destroy($publicId, [
                    'resource_type' => $resourceType
                ]);
                
                \Log::info('Résultat de la suppression Cloudinary', ['result' => $result]);
                return ['status' => 'success', 'result' => $result];
            } else {
                \Log::error('Aucun SDK Cloudinary disponible');
                return ['status' => 'error', 'message' => 'Aucun SDK Cloudinary disponible'];
            }
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression sur Cloudinary', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Extraire l'ID public d'une URL Cloudinary
     */
    private function extractPublicIdFromUrl($url)
    {
        // Si l'URL est vide, retourner null
        if (empty($url)) {
            return null;
        }
        
        try {
            // Vérifier si c'est une URL Cloudinary
            if (!$this->isCloudinaryUrl($url)) {
                return null;
            }
            
            // Extraire l'ID public
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'];
            
            // Format typique: /v1234567890/folder/public_id.extension
            // ou /video/upload/v1234567890/folder/public_id.extension
            
            // Supprimer le préfixe "/video/upload" ou "/image/upload" s'il existe
            $path = preg_replace('#^/(video|image)/upload/#', '/', $path);
            
            // Supprimer le préfixe "/v1234567890/"
            $path = preg_replace('#^/v\d+/#', '/', $path);
            
            // Supprimer l'extension
            $path = preg_replace('#\.[^.]+$#', '', $path);
            
            // Supprimer le premier slash
            if (strpos($path, '/') === 0) {
                $path = substr($path, 1);
            }
            
            \Log::info('ID public extrait de l\'URL', ['url' => $url, 'public_id' => $path]);
            
            return $path;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'extraction de l\'ID public', [
                'url' => $url,
                'message' => $e->getMessage()
            ]);
            
            return null;
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
     * Optimiser les options d'upload pour les vidéos
     */
    private function optimizeVideoUploadOptions($file, $options = [])
    {
        // Vérifier si c'est une vidéo
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        
        // Extensions et types MIME de vidéo
        $videoExtensions = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'flv', 'wmv', 'm4v', '3gp', 'mpeg', 'mpg'];
        $videoMimeTypes = [
            'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm',
            'video/x-matroska', 'video/x-flv', 'video/x-ms-wmv', 'video/3gpp'
        ];
        
        $isVideo = in_array($extension, $videoExtensions) || 
                  (strpos($mimeType, 'video/') !== false) ||
                  in_array($mimeType, $videoMimeTypes);
        
        if (!$isVideo) {
            return $options;
        }
        
        // Options de base pour les vidéos
        $videoOptions = array_merge($options, [
            'resource_type' => 'video',
            'chunk_size' => 6000000, // 6MB chunks pour les vidéos volumineuses
            'timeout' => 120 // Timeout plus long pour les vidéos
        ]);
        
        // Formats problématiques qui nécessitent une optimisation
        $problematicExtensions = ['mov', 'avi', 'wmv', 'flv'];
        $problematicMimeTypes = ['video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'video/x-flv'];
        
        $needsOptimization = in_array($extension, $problematicExtensions) || 
                           in_array($mimeType, $problematicMimeTypes);
        
        if ($needsOptimization) {
            \Log::info("Optimisation de la vidéo pour le format: {$extension} ({$mimeType})", [
                'file_name' => $file->getClientOriginalName(),
                'size' => $file->getSize()
            ]);
            
            // Options pour optimiser les formats problématiques
            $videoOptions = array_merge($videoOptions, [
                'eager' => [
                    [
                        'streaming_profile' => 'hd', // Profil de streaming HD
                        'format' => 'mp4' // Convertir en MP4
                    ]
                ],
                'eager_async' => true, // Traitement asynchrone des transformations
                'eager_notification_url' => env('APP_URL') . '/api/cloudinary-webhook' // URL de notification (optionnel)
            ]);
        }
        
        // Pour les vidéos volumineuses
        if ($file->getSize() > 50 * 1024 * 1024) { // Plus de 50MB
            \Log::info("Vidéo volumineuse détectée: " . ($file->getSize() / (1024 * 1024)) . " MB");
            
            // Options supplémentaires pour les vidéos volumineuses
            $videoOptions = array_merge($videoOptions, [
                'chunk_size' => 10000000, // 10MB chunks
                'timeout' => 300 // 5 minutes
            ]);
        }
        
        return $videoOptions;
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

    /**
     * Vérifier l'état du stockage des médias
     */
    public function checkStorage()
    {
        try {
            $result = [
                'storage' => [
                    'public_path_exists' => file_exists(public_path('storage')),
                    'public_media_exists' => file_exists(public_path('storage/media')),
                    'storage_path_exists' => file_exists(storage_path('app/public')),
                    'storage_media_exists' => file_exists(storage_path('app/public/media')),
                ],
                'permissions' => [
                    'public_storage_writable' => is_writable(public_path('storage')),
                    'storage_app_public_writable' => is_writable(storage_path('app/public')),
                ],
                'media_count' => Media::count(),
                'cloudinary_config' => [
                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME') ? 'Défini' : 'Non défini',
                    'api_key' => env('CLOUDINARY_KEY') ? 'Défini' : 'Non défini',
                    'api_secret' => env('CLOUDINARY_SECRET') ? 'Défini' : 'Non défini',
                    'url' => env('CLOUDINARY_URL') ? 'Défini' : 'Non défini',
                ],
                'test_creation' => [
                    'status' => 'pending'
                ]
            ];
            
            // Essayer de créer un fichier test
            try {
                $testFile = 'storage/media/test_' . time() . '.txt';
                file_put_contents(public_path($testFile), 'Test file creation');
                $result['test_creation'] = [
                    'status' => 'success',
                    'path' => $testFile,
                    'url' => url($testFile)
                ];
            } catch (\Exception $e) {
                $result['test_creation'] = [
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
            
            // Vérifier si le lien symbolique est correctement configuré
            $result['symlink_status'] = [
                'should_exist' => storage_path('app/public') . ' -> ' . public_path('storage'),
                'storage_exists' => is_dir(public_path('storage')),
                'command_to_create' => 'php artisan storage:link'
            ];
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
