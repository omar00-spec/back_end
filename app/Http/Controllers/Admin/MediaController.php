<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\MediaController as BaseMediaController;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
     * Stocke un nouveau média
     */
    public function store(Request $request)
    {
        return parent::store($request);
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