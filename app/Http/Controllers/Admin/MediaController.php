<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\MediaController as BaseMediaController;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class MediaController extends BaseMediaController
{
    /**
     * Affiche la liste des médias (photos et vidéos)
     */
    public function index(Request $request)
    {
        return parent::index($request);
    }

    /**
     * Stocke un nouveau média avec une gestion améliorée des vidéos
     */
    public function store(Request $request)
    {
        // Log complet de la requête pour le débogage
        \Log::info('Requête de création de média reçue (Admin)', [
            'all_data' => $request->all(),
            'has_file' => $request->hasFile('file'),
            'file_info' => $request->hasFile('file') ? [
                'name' => $request->file('file')->getClientOriginalName(),
                'size' => $request->file('file')->getSize(),
                'mime' => $request->file('file')->getMimeType(),
                'extension' => $request->file('file')->getClientOriginalExtension(),
            ] : null
        ]);
        
        // Validation des données avec des règles très permissives
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:photo,video,document',
            'category_id' => 'nullable|exists:categories,id',
            'file' => 'required|file|max:204800', // 200MB max pour permettre les vidéos volumineuses
        ]);

        if ($validator->fails()) {
            \Log::error('Validation échouée pour le média (Admin)', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all(),
                'file_info' => $request->hasFile('file') ? [
                    'name' => $request->file('file')->getClientOriginalName(),
                    'size' => $request->file('file')->getSize(),
                    'mime' => $request->file('file')->getMimeType(),
                    'extension' => $request->file('file')->getClientOriginalExtension(),
                ] : 'Pas de fichier'
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
                \Log::error('Fichier invalide ou non reçu (Admin)', [
                    'file_exists' => $request->hasFile('file'),
                    'all_files' => $request->allFiles(),
                    'request_data' => $request->all()
                ]);
                return response()->json([
                    'message' => 'Le fichier est invalide ou n\'a pas été reçu',
                    'error' => 'file_invalid'
                ], 400);
            }
            
            // Traitement spécial pour les vidéos
            if ($request->type === 'video') {
                \Log::info('Traitement spécial pour vidéo (Admin)', [
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension()
                ]);
                
                // Tous les types MIME acceptés pour les vidéos
                $acceptedMimeTypes = [
                    'video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 
                    'video/x-ms-wmv', 'video/x-flv', 'video/webm', 'video/x-matroska',
                    'video/3gpp', 'application/octet-stream'
                ];
                
                // Si le type MIME n'est pas dans la liste mais que l'extension est correcte, on continue
                $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv', '3gp', 'mpeg', 'mpg', 'm4v'];
                $extension = strtolower($file->getClientOriginalExtension());
                
                if (!in_array($file->getMimeType(), $acceptedMimeTypes) && !in_array($extension, $videoExtensions)) {
                    \Log::warning('Type de fichier vidéo non reconnu (Admin)', [
                        'extension' => $extension,
                        'mime_type' => $file->getMimeType(),
                        'accepted_mimes' => $acceptedMimeTypes,
                        'accepted_extensions' => $videoExtensions
                    ]);
                    
                    // On continue quand même, on fait confiance à l'utilisateur
                    \Log::info('Continuation malgré type non reconnu (Admin)');
                }
            }
            
            // Debug - Log des informations avant l'upload
            \Log::info('Tentative d\'upload sur Cloudinary (Admin)', [
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
            
            // Déterminer le dossier et les options en fonction du type
            $folder = 'acos_football/photos';
            $options = [];
            
            if ($request->type === 'video') {
                $folder = 'acos_football/videos';
                
                // Options spécifiques pour les vidéos
                $options = [
                    'resource_type' => 'video', // IMPORTANT: forcer le type à vidéo
                    'folder' => $folder,
                    'use_filename' => true,
                    'unique_filename' => true,
                    'overwrite' => false,
                    'chunk_size' => 6000000, // 6MB chunks pour les vidéos volumineuses
                    'timeout' => 900, // 15 minutes timeout
                    'eager' => [
                        ['streaming_profile' => 'hd', 'format' => 'mp4'],
                        ['quality' => 'auto', 'format' => 'mp4']
                    ],
                    'eager_async' => true,
                    'eager_notification_url' => env('APP_URL') . '/api/cloudinary-callback',
                    'transformation' => [
                        'quality' => 'auto',
                        'fetch_format' => 'auto'
                    ]
                ];
                
                \Log::info('Options d\'upload vidéo configurées (Admin)', $options);
                
                // Vérifier la taille de la vidéo et ajuster les options si nécessaire
                $fileSizeMB = round($file->getSize() / (1024 * 1024), 2);
                if ($fileSizeMB > 50) {
                    \Log::info("Vidéo volumineuse détectée: {$fileSizeMB}MB - Ajustement des options d'upload (Admin)");
                    $options['chunk_size'] = 10000000; // 10MB chunks pour les très grosses vidéos
                    $options['timeout'] = 1200; // 20 minutes timeout
                    
                    // Ajout d'informations pour le suivi de l'upload
                    $options['notification_url'] = env('APP_URL') . '/api/cloudinary-notification';
                }
            } else {
                // Options pour les photos et autres fichiers
                $options = [
                    'resource_type' => 'auto',
                    'folder' => $folder,
                    'use_filename' => true,
                    'unique_filename' => true,
                    'overwrite' => false,
                ];
            }

            try {
                // Tentative d'upload sur Cloudinary avec les options spécifiques
                \Log::info('Début upload Cloudinary avec options (Admin)', [
                    'resource_type' => $options['resource_type'],
                    'folder' => $folder,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_mime' => $file->getMimeType()
                ]);
                
                // Upload sur Cloudinary
                $uploadResult = Cloudinary::upload($file->getRealPath(), $options);
                
                \Log::info('Résultat upload Cloudinary (Admin)', [
                    'secure_url' => $uploadResult->getSecurePath(),
                    'public_id' => $uploadResult->getPublicId(),
                    'resource_type' => $uploadResult->getResourceType(),
                    'format' => $uploadResult->getExtension()
                ]);
                
                // Créer l'entrée dans la base de données
                try {
                    // Vérifier à nouveau que l'URL est définie
                    if (empty($uploadResult->getSecurePath())) {
                        \Log::error('URL de fichier vide avant création en BDD (Admin)');
                        return response()->json([
                            'message' => 'URL du fichier vide, impossible de créer l\'entrée en base de données',
                            'error' => 'empty_file_url'
                        ], 500);
                    }
                    
                    $media = new Media();
                    $media->title = $request->title;
                    $media->type = $request->type;
                    $media->category_id = $request->category_id;
                    $media->file_path = $uploadResult->getSecurePath(); // URL Cloudinary
                    
                    // Log avant sauvegarde
                    \Log::info('Tentative de sauvegarde média en BDD (Admin)', [
                        'title' => $media->title,
                        'type' => $media->type,
                        'category_id' => $media->category_id,
                        'file_path' => $media->file_path
                    ]);
                    
                    $media->save();
                    
                    \Log::info('Média sauvegardé en BDD (Admin)', [
                        'id' => $media->id, 
                        'url' => $media->file_path,
                        'type' => $media->type,
                        'category_id' => $media->category_id
                    ]);
                    
                    return response()->json([
                        'message' => 'Média téléchargé avec succès sur Cloudinary',
                        'media' => $media
                    ], 201);
                } catch (\Exception $dbError) {
                    \Log::error('Erreur lors de la sauvegarde en base de données (Admin)', [
                        'error' => $dbError->getMessage(),
                        'trace' => $dbError->getTraceAsString(),
                        'media_data' => [
                            'title' => $request->title,
                            'type' => $request->type,
                            'category_id' => $request->category_id,
                            'file_path' => $uploadResult->getSecurePath() ?? 'NULL'
                        ]
                    ]);
                    return response()->json([
                        'message' => 'Erreur lors de la sauvegarde en base de données',
                        'error' => $dbError->getMessage()
                    ], 500);
                }
            } catch (\Exception $cloudinaryError) {
                // Log détaillé de l'erreur Cloudinary
                \Log::error('Erreur Cloudinary détaillée (Admin)', [
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
        } catch (\Exception $e) {
            \Log::error('Erreur générale lors du téléchargement du média (Admin)', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erreur lors du téléchargement du média',
                'error' => $e->getMessage()
            ], 500);
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
        return parent::update($request, $id);
    }

    /**
     * Supprime un média
     */
    public function destroy($id)
    {
        // Utiliser la méthode destroy du contrôleur parent
        // mais avec un paramètre ID au lieu d'un objet Media
        $media = Media::findOrFail($id);
        return parent::destroy($media);
    }
}