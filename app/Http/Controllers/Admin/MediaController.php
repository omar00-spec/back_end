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
        Log::info('Admin\MediaController::store appelé', [
            'request_has_file' => $request->hasFile('file'),
            'request_all' => $request->all(),
            'request_files' => $request->allFiles()
        ]);
        return parent::store($request);
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