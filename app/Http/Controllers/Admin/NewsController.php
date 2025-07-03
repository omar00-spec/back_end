<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    /**
     * Affiche la liste des actualités et événements
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'all');

        if ($type === 'news') {
            $items = News::where('type', 'news')->orderBy('created_at', 'desc')->get();
        } elseif ($type === 'event') {
            $items = News::where('type', 'event')->orderBy('created_at', 'desc')->get();
        } else {
            $items = News::orderBy('created_at', 'desc')->get();
        }

        // Ajouter les URLs complètes pour les images
        foreach ($items as $item) {
            if ($item->image) {
                if (!filter_var($item->image, FILTER_VALIDATE_URL)) {
                    if (strpos($item->image, 'news/') === 0) {
                        $item->image_url = asset('storage/' . $item->image);
                    } else {
                        $item->image_url = asset('storage/news/' . $item->image);
                    }
                } else {
                    $item->image_url = $item->image;
                }
            }
        }

        return response()->json($items);
    }

    /**
     * Stocke une nouvelle actualité ou événement
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:news,event',
            'location' => 'nullable|string|max:255',
            'event_time' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Préparer les données de l'actualité
        $newsData = $request->except('image');
        
        // Ajouter la date actuelle si elle n'est pas fournie
        if (!isset($newsData['date']) || empty($newsData['date'])) {
            $newsData['date'] = now()->format('Y-m-d');
        }
        
        // Traiter l'upload d'image si présent
        if ($request->hasFile('image')) {
            // Générer un nom unique pour l'image
            $file = $request->file('image');
            $filename = 'news_' . Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // S'assurer que les répertoires existent
            if (!Storage::disk('public')->exists('news')) {
                Storage::disk('public')->makeDirectory('news');
            }
            
            if (!file_exists(public_path('storage/news'))) {
                mkdir(public_path('storage/news'), 0755, true);
            }
            
            // Sauvegarder l'image dans storage/app/public/news
            $path = $file->storeAs('news', $filename, 'public');
            
            // Copier l'image vers public/storage/news pour assurer la compatibilité
            $sourcePath = storage_path('app/public/news/' . $filename);
            $destPath = public_path('storage/news/' . $filename);
            
            // Vérifier que le fichier source existe
            if (file_exists($sourcePath)) {
                // Copier le fichier
                copy($sourcePath, $destPath);
            } else {
                // Si le fichier source n'existe pas, essayer de le sauvegarder directement
                $file->move(public_path('storage/news'), $filename);
            }
            
            // Sauvegarder le chemin de l'image dans la base de données
            $newsData['image'] = $path;
        }
        // Si c'est une URL d'image externe, on la laisse telle quelle
        elseif ($request->has('image_url') && filter_var($request->image_url, FILTER_VALIDATE_URL)) {
            $newsData['image'] = $request->image_url;
        }
        
        // Créer l'actualité
        $news = News::create($newsData);
        
        // Si l'image était une URL et que nous voulons la sauvegarder localement
        if ($news->image && filter_var($news->image, FILTER_VALIDATE_URL)) {
            // Essayer de télécharger l'image depuis l'URL
            try {
                $imageContent = @file_get_contents($news->image);
                if ($imageContent !== false) {
                    $filename = 'news_' . $news->id . '_' . Str::random(5) . '_' . time() . '.jpg';
                    $path = 'news/' . $filename;
                    
                    // Sauvegarder l'image téléchargée
                    Storage::disk('public')->put($path, $imageContent);
                    
                    // Mettre à jour le chemin de l'image dans la base de données
                    $news->image = $path;
                    $news->save();
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs de téléchargement mais les journaliser
                \Log::error('Erreur lors du téléchargement de l\'image: ' . $e->getMessage());
            }
        }

        return response()->json($news, 201);
    }

    /**
     * Affiche une actualité ou un événement spécifique
     */
    public function show($id)
    {
        $news = News::findOrFail($id);
        
        // Ajouter l'URL complète pour l'image
        if ($news->image) {
            if (!filter_var($news->image, FILTER_VALIDATE_URL)) {
                if (strpos($news->image, 'news/') === 0) {
                    $news->image_url = asset('storage/' . $news->image);
                } else {
                    $news->image_url = asset('storage/news/' . $news->image);
                }
            } else {
                $news->image_url = $news->image;
            }
        }
        
        return response()->json($news);
    }

    /**
     * Met à jour une actualité ou un événement existant
     */
    public function update(Request $request, $id)
    {
        $news = News::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:news,event',
            'location' => 'nullable|string|max:255',
            'event_time' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'date' => 'sometimes|required|date',
            'status' => 'nullable|string|in:Brouillon,Publié'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Préparer les données de mise à jour
        $updateData = $request->except(['image']);
        
        // Si seul le statut est fourni, ne mettre à jour que le statut
        if ($request->has('status') && count($updateData) === 1) {
            $news->status = $request->status;
            $news->save();
            return response()->json($news);
        }
        
        // Traiter l'upload d'image si présent
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($news->image && !filter_var($news->image, FILTER_VALIDATE_URL)) {
                // Nettoyer le chemin d'image
                $oldImagePath = $news->image;
                
                // Supprimer de storage/app/public
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
                
                // Supprimer de public/storage
                $oldPublicPath = public_path('storage/' . $oldImagePath);
                if (file_exists($oldPublicPath)) {
                    unlink($oldPublicPath);
                }
            }
            
            // Générer un nom unique pour la nouvelle image
            $file = $request->file('image');
            $filename = 'news_' . Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // S'assurer que les répertoires existent
            if (!Storage::disk('public')->exists('news')) {
                Storage::disk('public')->makeDirectory('news');
            }
            
            if (!file_exists(public_path('storage/news'))) {
                mkdir(public_path('storage/news'), 0755, true);
            }
            
            // Sauvegarder l'image dans storage/app/public/news
            $path = $file->storeAs('news', $filename, 'public');
            
            // Copier l'image vers public/storage/news pour assurer la compatibilité
            $sourcePath = storage_path('app/public/news/' . $filename);
            $destPath = public_path('storage/news/' . $filename);
            
            // Vérifier que le fichier source existe
            if (file_exists($sourcePath)) {
                // Copier le fichier
                copy($sourcePath, $destPath);
            } else {
                // Si le fichier source n'existe pas, essayer de le sauvegarder directement
                $file->move(public_path('storage/news'), $filename);
                
                // S'assurer que le fichier existe aussi dans storage/app/public/news
                if (!file_exists(dirname($sourcePath))) {
                    mkdir(dirname($sourcePath), 0755, true);
                }
                copy($destPath, $sourcePath);
            }
            
            // Mettre à jour le chemin de l'image dans les données
            $updateData['image'] = $path;
        }
        
        // Mettre à jour l'actualité
        $news->update($updateData);
        
        return response()->json($news);
    }

    /**
     * Supprime une actualité ou un événement
     */
    public function destroy($id)
    {
        $news = News::findOrFail($id);
        
        // Supprimer l'image associée si elle existe et n'est pas une URL externe
        if ($news->image && !filter_var($news->image, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($news->image);
        }
        
        $news->delete();

        return response()->json(null, 204);
    }

    /**
     * Publie une actualité
     */
    public function publish($id)
    {
        $news = News::findOrFail($id);
        $news->status = 'Publié';
        $news->save();
        
        return response()->json($news);
    }
    
    /**
     * Dépublie une actualité
     */
    public function unpublish($id)
    {
        $news = News::findOrFail($id);
        $news->status = 'Brouillon';
        $news->save();
        
        return response()->json($news);
    }
}
