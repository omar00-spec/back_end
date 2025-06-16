<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        // Filtrer par statut pour ne montrer que les actualités publiées
        $query = News::where('status', 'Publié');
        
        // Si le type est spécifié, filtrer par type
        if ($request->has('type')) {
            $news = $query->where('type', $request->type)->get();
        } else {
            // Sinon, retourner toutes les actualités/événements publiés
            $news = $query->get();
        }
        
        // Ajouter l'URL complète pour les images
        foreach ($news as $item) {
            $this->formatImageUrl($item);
        }
        
        return $news;
    }

    public function getEvents()
    {
        // Ne récupérer que les événements publiés
        $events = News::where('type', 'event')
                      ->where('status', 'Publié')
                      ->get();
        
        // Ajouter l'URL complète pour les images
        foreach ($events as $event) {
            $this->formatImageUrl($event);
        }
        
        return $events;
    }

    public function getNews()
    {
        // Ne récupérer que les actualités publiées
        $news = News::where('type', 'news')
                    ->where('status', 'Publié')
                    ->get();
        
        // Ajouter l'URL complète pour les images
        foreach ($news as $item) {
            $this->formatImageUrl($item);
        }
        
        return $news;
    }

    public function store(Request $request)
    {
        // Valider les données
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:news,event',
            'location' => 'nullable|string|max:255',
            'event_time' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'date' => 'nullable|date'
        ]);

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
            
            // Sauvegarder l'image dans storage/app/public/news
            $path = $file->storeAs('news', $filename, 'public');
            
            // Copier l'image vers public/storage/news pour assurer la compatibilité
            $sourcePath = storage_path('app/public/news/' . $filename);
            $destPath = public_path('storage/news/' . $filename);
            
            // Créer le répertoire de destination s'il n'existe pas
            if (!file_exists(dirname($destPath))) {
                mkdir(dirname($destPath), 0755, true);
            }
            
            // Copier le fichier
            if (file_exists($sourcePath)) {
                copy($sourcePath, $destPath);
            }
            
            // Sauvegarder le chemin de l'image dans la base de données
            $newsData['image'] = $path;
        }
        
        // Créer l'actualité
        $news = News::create($newsData);
        
        // Formater l'URL de l'image pour la réponse API
        $this->formatImageUrl($news);
        
        return $news;
    }

    public function show(News $news)
    {
        // Ajouter l'URL complète pour l'image
        $this->formatImageUrl($news);
        
        return $news;
    }

    public function update(Request $request, News $news)
    {
        // Valider les données
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:news,event',
            'location' => 'nullable|string|max:255',
            'event_time' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'date' => 'sometimes|required|date',
            'status' => 'nullable|string|in:Brouillon,Publié'
        ]);
        
        // Préparer les données de mise à jour
        $updateData = $request->except('image');
        
        // Si seul le statut est fourni, ne mettre à jour que le statut
        if ($request->has('status') && count($updateData) === 1) {
            $news->status = $request->status;
            $news->save();
            
            // Formater l'URL de l'image pour la réponse API
            $this->formatImageUrl($news);
            
            return $news;
        }
        
        // Traiter l'upload d'image si présent
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe et n'est pas une URL externe
            if ($news->image && !filter_var($news->image, FILTER_VALIDATE_URL) && strpos($news->image, 'news/') === 0) {
                // Nettoyer le chemin d'image pour éviter les erreurs de caractères spéciaux
                $cleanImagePath = trim($news->image);
                Storage::disk('public')->delete($cleanImagePath);
                
                // Supprimer également l'image du répertoire public/storage si elle existe
                $oldPublicPath = public_path('storage/' . $cleanImagePath);
                if (file_exists($oldPublicPath)) {
                    unlink($oldPublicPath);
                }
            }
            
            // Générer un nom unique pour la nouvelle image
            $file = $request->file('image');
            $filename = 'news_' . $news->id . '_' . Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Sauvegarder l'image dans storage/app/public/news
            $path = $file->storeAs('news', $filename, 'public');
            
            // Copier l'image vers public/storage/news pour assurer la compatibilité
            $sourcePath = storage_path('app/public/news/' . $filename);
            $destPath = public_path('storage/news/' . $filename);
            
            // Créer le répertoire de destination s'il n'existe pas
            if (!file_exists(dirname($destPath))) {
                mkdir(dirname($destPath), 0755, true);
            }
            
            // Copier le fichier
            if (file_exists($sourcePath)) {
                copy($sourcePath, $destPath);
            }
            
            // Sauvegarder le chemin de l'image dans la base de données
            $updateData['image'] = $path;
        }
        
        $news->update($updateData);
        
        // Formater l'URL de l'image pour la réponse API
        $this->formatImageUrl($news);
        
        return $news;
    }

    public function destroy(News $news)
    {
        // Supprimer l'image associée si elle existe et n'est pas une URL externe
        if ($news->image && !filter_var($news->image, FILTER_VALIDATE_URL) && strpos($news->image, 'news/') === 0) {
            Storage::disk('public')->delete($news->image);
        }
        
        $news->delete();
        return response()->noContent();
    }
    
    /**
     * Formate l'URL de l'image pour l'afficher correctement
     */
    protected function formatImageUrl($item) 
    {
        if (!$item->image) {
            $item->image = asset('images/default-news.jpg');
            return;
        }
        
        // Vérifier si c'est déjà une URL complète
        if (filter_var($item->image, FILTER_VALIDATE_URL)) {
            return;
        }
        
        // Le plus simple : on préfixe avec storage/ quelle que soit la valeur
        $imagePath = $item->image;
        
        // Si le chemin commence déjà par "storage/", on ne modifie rien
        if (strpos($imagePath, 'storage/') === 0) {
            $item->image = url($imagePath);
        } 
        // Si le chemin commence par "news/" ou contient "news/"
        else if (strpos($imagePath, 'news/') !== false) {
            $finalPath = 'storage/' . $imagePath;
            $item->image = url($finalPath);
            
            // Si le fichier n'existe pas, essayer d'autres chemins possibles
            $physicalPath = public_path($finalPath);
            if (!file_exists($physicalPath)) {
                $item->image = url('storage/news/' . basename($imagePath));
            }
        }
        // Si le chemin commence par "/"
        else if (strpos($imagePath, '/') === 0) {
            $item->image = url(substr($imagePath, 1));
        }
        // Dans tous les autres cas, on suppose que c'est simplement un nom de fichier dans news/
        else {
            // Vérifier si le fichier existe dans storage/news/
            $physicalPath = storage_path('app/public/news/' . $imagePath);
            
            if (file_exists($physicalPath)) {
                $finalPath = 'storage/news/' . $imagePath;
                $item->image = url($finalPath);
            } else {
                // Essayer avec juste le nom de fichier
                $finalPath = 'storage/news/' . basename($imagePath);
                $item->image = url($finalPath);
            }
        }
    }
}
