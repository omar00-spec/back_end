<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\MediaController as BaseMediaController;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class MediaController extends BaseMediaController
{
    /**
     * Affiche la liste des médias (photos et vidéos)
     */
    public function index(Request $request)
    {
        Log::info('Admin\MediaController::index appelé', [
            'request_params' => $request->all(),
            'request_path' => $request->path(),
            'request_url' => $request->url(),
        ]);
        
        try {
            $result = parent::index($request);
            
            // Log du résultat
            if ($result instanceof \Illuminate\Database\Eloquent\Collection) {
                Log::info('Résultat de Admin\MediaController::index', [
                    'count' => $result->count(),
                    'first_media' => $result->first() ? [
                        'id' => $result->first()->id,
                        'title' => $result->first()->title,
                        'type' => $result->first()->type,
                        'file_path' => $result->first()->file_path,
                    ] : null
                ]);
            } else {
                Log::warning('Résultat inattendu de Admin\MediaController::index', [
                    'type' => gettype($result),
                    'result' => $result
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Erreur dans Admin\MediaController::index', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erreur lors de la récupération des médias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stocke un nouveau média
     */
    public function store(Request $request)
    {
        \Log::info('Tentative de création de média par admin', [
            'admin_id' => auth()->id(),
            'request_data' => $request->except(['file']),
            'file_present' => $request->hasFile('file')
        ]);
        
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'type' => 'required|in:photo,video,document',
                'file' => 'required|file|max:102400', // 100MB max
            ]);
            
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                
                // Vérifier le type de fichier
                $fileType = $file->getMimeType();
                $fileExtension = strtolower($file->getClientOriginalExtension());
                
                \Log::info('Fichier reçu pour création', [
                    'nom' => $file->getClientOriginalName(),
                    'extension' => $fileExtension,
                    'type_mime' => $fileType,
                    'taille' => $file->getSize()
                ]);
                
                // Vérifier la cohérence entre le type demandé et le fichier
                $isValidType = true;
                
                if ($request->type === 'photo' && !str_starts_with($fileType, 'image/')) {
                    $isValidType = false;
                    \Log::warning('Type de fichier incohérent pour une photo', [
                        'type_demandé' => 'photo',
                        'mime_type' => $fileType
                    ]);
                } elseif ($request->type === 'video') {
                    // Pour les vidéos, on est plus permissif car certains formats peuvent avoir des types MIME variés
                    $videoExtensions = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'flv', 'wmv', 'm4v', '3gp', 'mpg', 'mpeg'];
                    $isVideoByExtension = in_array($fileExtension, $videoExtensions);
                    $isVideoByMimeType = str_starts_with($fileType, 'video/');
                    
                    if (!$isVideoByExtension && !$isVideoByMimeType) {
                        $isValidType = false;
                        \Log::warning('Type de fichier incohérent pour une vidéo', [
                            'type_demandé' => 'video',
                            'extension' => $fileExtension,
                            'mime_type' => $fileType
                        ]);
                    }
                    
                    // Vérifier si c'est un format problématique
                    $problematicExtensions = ['mov', 'avi', 'wmv', 'flv'];
                    $problematicMimeTypes = ['video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'video/x-flv'];
                    
                    if (in_array($fileExtension, $problematicExtensions) || in_array($fileType, $problematicMimeTypes)) {
                        \Log::warning('Format vidéo potentiellement problématique détecté', [
                            'extension' => $fileExtension,
                            'mime_type' => $fileType,
                            'taille' => $file->getSize()
                        ]);
                    }
                }
                
                if (!$isValidType) {
                    return response()->json([
                        'message' => 'Le type de fichier ne correspond pas au type de média sélectionné'
                    ], 400);
                }
                
                // Créer le média
                $result = app(\App\Http\Controllers\MediaController::class)->upload($request);
                
                if ($result->getStatusCode() !== 200) {
                    \Log::error('Erreur lors de l\'upload du média', [
                        'status_code' => $result->getStatusCode(),
                        'response' => json_decode($result->getContent(), true)
                    ]);
                    
                    return $result;
                }
                
                $responseData = json_decode($result->getContent(), true);
                
                if (!isset($responseData['id'])) {
                    \Log::error('Réponse d\'upload invalide', [
                        'response' => $responseData
                    ]);
                    
                    return response()->json([
                        'message' => 'Erreur lors de la création du média: réponse invalide'
                    ], 500);
                }
                
                // Récupérer le média créé
                $media = \App\Models\Media::find($responseData['id']);
                
                if (!$media) {
                    \Log::error('Média non trouvé après création', [
                        'media_id' => $responseData['id']
                    ]);
                    
                    return response()->json([
                        'message' => 'Erreur lors de la création du média: média non trouvé'
                    ], 500);
                }
                
                \Log::info('Média créé avec succès', [
                    'media_id' => $media->id,
                    'title' => $media->title,
                    'file_path' => $media->file_path
                ]);
                
                return response()->json($media);
            } else {
                \Log::error('Aucun fichier fourni pour la création du média');
                
                return response()->json([
                    'message' => 'Aucun fichier fourni'
                ], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Exception lors de la création du média', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erreur lors de la création du média: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche un média spécifique
     */
    public function show($id)
    {
        Log::info('Admin\MediaController::show appelé', [
            'id' => $id
        ]);
        $media = Media::findOrFail($id);
        return response()->json($media);
    }

    /**
     * Met à jour un média existant
     */
    public function update(Request $request, $id)
    {
        Log::info('Admin\MediaController::update appelé', [
            'id' => $id,
            'request_has_file' => $request->hasFile('file'),
            'request_all' => $request->all(),
            'request_files' => $request->allFiles()
        ]);
        return parent::update($request, $id);
    }

    /**
     * Supprime un média
     */
    public function destroy($id)
    {
        Log::info('Admin\MediaController::destroy appelé', [
            'id' => $id
        ]);
        // Utiliser la méthode destroy du contrôleur parent
        // mais avec un paramètre ID au lieu d'un objet Media
        $media = Media::findOrFail($id);
        return parent::destroy($media);
    }
}