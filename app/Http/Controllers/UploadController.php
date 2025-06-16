<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\News;
use App\Models\Media;
use Illuminate\Support\Facades\File;

class UploadController extends Controller
{
    /**
     * Upload une image et la lie à l'actualité correspondante
     */
    public function uploadNewsImage(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'news_id' => 'required|exists:news,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Récupération de l'actualité
        $news = News::findOrFail($request->news_id);
        
        // Upload de l'image
        $file = $request->file('image');
        $path = $this->uploadFile($file, 'news');

        // Copier l'image vers public/storage/news pour assurer la compatibilité
        $filename = basename($path);
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

        // Suppression de l'ancienne image si elle existe
        if ($news->image && Storage::disk('public')->exists($news->image)) {
            // Nettoyer le chemin d'image pour éviter les erreurs de caractères spéciaux
            $cleanImagePath = trim($news->image);
            Storage::disk('public')->delete($cleanImagePath);
            
            // Supprimer également l'image du répertoire public/storage si elle existe
            $oldPublicPath = public_path('storage/' . $cleanImagePath);
            if (file_exists($oldPublicPath)) {
                unlink($oldPublicPath);
            }
        }

        // Mise à jour de l'actualité avec le chemin de la nouvelle image
        $news->image = $path;
        $news->save();

        return response()->json([
            'success' => true,
            'message' => 'Image téléchargée avec succès',
            'path' => $path,
            'url' => asset('storage/' . $path)
        ]);
    }

    /**
     * Upload un fichier média (photo ou vidéo)
     */
    public function uploadMedia(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpeg,png,jpg,mp4,webm|max:10240',
            'media_id' => 'required|exists:media,id',
            'type' => 'required|in:photo,video'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Récupération du média
        $media = Media::findOrFail($request->media_id);

        // Upload du fichier
        $file = $request->file('file');
        $filename = 'media_' . $media->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $this->uploadFile($file, 'media', $filename);

        // Suppression de l'ancien fichier s'il existe
        if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        // Mise à jour du média avec le nouveau chemin
        $media->file_path = $path;
        $media->save();

        return response()->json([
            'success' => true, 
            'message' => 'Fichier média téléchargé avec succès',
            'path' => $path,
            'url' => asset('storage/' . $path)
        ]);
    }
    
    /**
     * Méthode privée pour gérer l'upload de fichiers
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @param string|null $filename
     * @return string
     */
    private function uploadFile($file, $directory, $filename = null)
    {
        // S'assurer que le répertoire existe
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }
        
        // Si aucun nom de fichier n'est fourni, utiliser la méthode store
        if (!$filename) {
            return $file->store($directory, 'public');
        }
        
        // Sinon, utiliser storeAs avec le nom de fichier spécifié
        return $file->storeAs($directory, $filename, 'public');
    }
    
    /**
     * Vérifie et corrige les chemins d'images dans la base de données
     */
    public function fixImagePaths()
    {
        // Vérifiez si le dossier news existe, si non, le créer
        if (!Storage::disk('public')->exists('news')) {
            Storage::disk('public')->makeDirectory('news');
        }
        
        $news = News::all();
        $fixed = [];
        $notFound = [];
        $copied = [];
        
        // Vérifier toutes les images dans le répertoire public/storage
        $allFiles = File::glob(public_path('storage/*.*'));
        $publicStorageImages = [];
        foreach ($allFiles as $file) {
            if (in_array(File::extension($file), ['jpg', 'jpeg', 'png', 'gif'])) {
                $publicStorageImages[] = $file;
            }
        }
        
        // Vérifier aussi dans le répertoire storage/app/public
        $allStorageFiles = File::glob(storage_path('app/public/*.*'));
        $storageAppPublicImages = [];
        foreach ($allStorageFiles as $file) {
            if (in_array(File::extension($file), ['jpg', 'jpeg', 'png', 'gif'])) {
                $storageAppPublicImages[] = $file;
            }
        }
        
        foreach ($news as $item) {
            if (!$item->image) {
                continue; // Pas d'image à fixer
            }
            
            // Si l'image est déjà une URL complète, on la traite différemment
            if (filter_var($item->image, FILTER_VALIDATE_URL)) {
                // Extraction du nom de fichier de l'URL
                $filename = basename(parse_url($item->image, PHP_URL_PATH));
                
                // On vérifie si l'image existe dans le stockage
                if (Storage::disk('public')->exists('news/' . $filename)) {
                    $item->image = 'news/' . $filename;
                    $item->save();
                    $fixed[] = [
                        'id' => $item->id,
                        'from' => 'URL complète',
                        'to' => $item->image
                    ];
                } else {
                    // Essayer de télécharger l'image depuis l'URL
                    try {
                        $imageContent = file_get_contents($item->image);
                        if ($imageContent !== false) {
                            Storage::disk('public')->put('news/' . $filename, $imageContent);
                            $item->image = 'news/' . $filename;
                            $item->save();
                            $copied[] = [
                                'id' => $item->id,
                                'from' => 'URL externe',
                                'to' => $item->image
                            ];
                        }
                    } catch (\Exception $e) {
                        $notFound[] = [
                            'id' => $item->id,
                            'image' => $item->image
                        ];
                    }
                }
                continue;
            }
            
            // Si l'image commence par news/ mais n'existe pas, essayons de la trouver ailleurs
            if (strpos($item->image, 'news/') === 0 && !Storage::disk('public')->exists($item->image)) {
                $filename = basename($item->image);
                $found = false;
                
                // Chercher dans /public/storage
                foreach ($publicStorageImages as $imagePath) {
                    if (basename($imagePath) === $filename) {
                        // Copier le fichier dans le répertoire news/
                        File::copy($imagePath, storage_path('app/public/news/' . $filename));
                        $copied[] = [
                            'id' => $item->id,
                            'from' => $imagePath,
                            'to' => 'news/' . $filename
                        ];
                        $found = true;
                        break;
                    }
                }
                
                // Chercher dans storage/app/public
                if (!$found) {
                    foreach ($storageAppPublicImages as $imagePath) {
                        if (basename($imagePath) === $filename) {
                            // Copier le fichier dans le répertoire news/
                            File::copy($imagePath, storage_path('app/public/news/' . $filename));
                            $copied[] = [
                                'id' => $item->id,
                                'from' => $imagePath,
                                'to' => 'news/' . $filename
                            ];
                            $found = true;
                            break;
                        }
                    }
                }
            } 
            // On normalise le chemin pour qu'il commence par 'news/'
            else if (strpos($item->image, 'news/') !== 0) {
                $filename = basename($item->image);
                
                // Vérifier si le fichier existe déjà dans news/
                if (Storage::disk('public')->exists('news/' . $filename)) {
                    $item->image = 'news/' . $filename;
                    $item->save();
                    $fixed[] = [
                        'id' => $item->id,
                        'from' => $item->image,
                        'to' => 'news/' . $filename
                    ];
                } 
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => count($fixed) . ' chemins d\'images corrigés, ' . count($copied) . ' images copiées, ' . count($notFound) . ' images non trouvées'
        ]);
    }
    
    /**
     * Vérifie et corrige les chemins des fichiers média dans la base de données
     */
    public function fixMediaPaths()
    {
        $media = Media::all();
        $fixed = [];
        $notFound = [];
        
        foreach ($media as $item) {
            if (!$item->file_path) {
                continue; // Pas de chemin à corriger
            }
            
            // Si le chemin est déjà une URL complète
            if (filter_var($item->file_path, FILTER_VALIDATE_URL)) {
                $urlPath = parse_url($item->file_path, PHP_URL_PATH);
                $filename = basename($urlPath);
                
                // Vérifier si le fichier existe dans le stockage
                if (Storage::disk('public')->exists('media/' . $filename)) {
                    $oldPath = $item->file_path;
                    $item->file_path = 'media/' . $filename;
                    $item->save();
                    $fixed[] = [
                        'id' => $item->id,
                        'from' => $oldPath,
                        'to' => $item->file_path
                    ];
                } else {
                    $notFound[] = [
                        'id' => $item->id,
                        'file_path' => $item->file_path,
                        'reason' => 'URL externe'
                    ];
                }
                continue;
            }
            
            // Si le chemin ne commence pas par "media/"
            if (strpos($item->file_path, 'media/') !== 0) {
                $filename = basename($item->file_path);
                
                // Vérifier si le fichier existe dans le stockage
                if (Storage::disk('public')->exists('media/' . $filename)) {
                    $oldPath = $item->file_path;
                    $item->file_path = 'media/' . $filename;
                    $item->save();
                    $fixed[] = [
                        'id' => $item->id,
                        'from' => $oldPath,
                        'to' => $item->file_path
                    ];
                } else {
                    $notFound[] = [
                        'id' => $item->id,
                        'file_path' => $item->file_path,
                        'reason' => 'Fichier introuvable'
                    ];
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'fixed' => $fixed,
            'not_found' => $notFound
        ]);
    }
    
    /**
     * Vérifie et répare spécifiquement une image par son nom de fichier
     */
    public function repairSpecificImage(Request $request)
    {
        // Valider la requête
        $validated = $request->validate([
            'filename' => 'required|string',
        ]);
        
        $filename = $request->filename;
        $allNews = News::all();
        $result = [
            'status' => 'not_found',
            'message' => 'Image non trouvée dans la base de données',
            'filename' => $filename
        ];
        
        // Chercher toutes les actualités utilisant cette image
        foreach ($allNews as $news) {
            if ($news->image && (basename($news->image) == $filename || strpos($news->image, $filename) !== false)) {
                $result['found_in_news'] = true;
                $result['status'] = 'found';
                
                // Vérifier si le fichier existe dans le dossier news/
                if (!Storage::disk('public')->exists('news/' . $filename)) {
                    // Chercher le fichier dans différents endroits
                    $possibleLocations = [
                        storage_path('app/public/') . $filename,
                        public_path('storage/') . $filename,
                        public_path('images/') . $filename,
                        public_path('storage/news/') . $filename
                    ];
                    
                    foreach ($possibleLocations as $location) {
                        if (file_exists($location)) {
                            // Copier le fichier dans le bon répertoire
                            try {
                                // Assurez-vous que le dossier news/ existe
                                if (!Storage::disk('public')->exists('news')) {
                                    Storage::disk('public')->makeDirectory('news');
                                }
                                
                                // Copier le fichier
                                copy($location, storage_path('app/public/news/' . $filename));
                                
                                // Mettre à jour le chemin d'image dans la base de données
                                $news->image = 'news/' . $filename;
                                $news->save();
                                
                                $result['status'] = 'fixed';
                                $result['message'] = 'Image trouvée et chemin corrigé dans la base de données';
                            } catch (\Exception $e) {
                                $result['error'] = 'Erreur lors de la copie du fichier';
                            }
                            break;
                        }
                    }
                } else {
                    // Le fichier existe au bon endroit, mettre à jour la référence si nécessaire
                    if ($news->image !== 'news/' . $filename) {
                        $news->image = 'news/' . $filename;
                        $news->save();
                        $result['status'] = 'fixed';
                        $result['message'] = 'Référence dans la base de données corrigée';
                    } else {
                        $result['status'] = 'ok';
                        $result['message'] = 'Image correctement référencée';
                    }
                }
                break;
            }
        }
        
        return response()->json($result);
    }
    
    /**
     * Vérifie et répare spécifiquement un fichier média par son nom de fichier
     */
    public function repairSpecificMedia(Request $request)
    {
        // Valider la requête
        $validated = $request->validate([
            'filename' => 'required|string',
        ]);
        
        $filename = $request->filename;
        $allMedia = Media::all();
        $result = [
            'status' => 'not_found',
            'message' => 'Fichier non trouvé dans la base de données',
            'filename' => $filename
        ];
        
        // Chercher tous les médias utilisant ce fichier
        foreach ($allMedia as $media) {
            if ($media->file_path && (basename($media->file_path) == $filename || strpos($media->file_path, $filename) !== false)) {
                $result['found_in_media'] = true;
                $result['media_type'] = $media->type;
                $result['status'] = 'found';
                
                // Vérifier si le fichier existe dans le dossier media/
                if (!Storage::disk('public')->exists('media/' . $filename)) {
                    // Chercher le fichier dans différents endroits
                    $possibleLocations = [
                        storage_path('app/public/') . $filename,
                        public_path('storage/') . $filename,
                        public_path('images/') . $filename,
                        public_path('videos/') . $filename,
                        public_path('storage/media/') . $filename
                    ];
                    
                    foreach ($possibleLocations as $location) {
                        if (file_exists($location)) {
                            // Copier le fichier dans le bon répertoire
                            try {
                                // Assurez-vous que le dossier media/ existe
                                if (!Storage::disk('public')->exists('media')) {
                                    Storage::disk('public')->makeDirectory('media');
                                }
                                
                                // Copier le fichier
                                copy($location, storage_path('app/public/media/' . $filename));
                                
                                // Mettre à jour le chemin du fichier dans la base de données
                                $media->file_path = 'media/' . $filename;
                                $media->save();
                                
                                $result['status'] = 'fixed';
                                $result['message'] = 'Fichier trouvé et chemin corrigé dans la base de données';
                            } catch (\Exception $e) {
                                $result['error'] = 'Erreur lors de la copie du fichier';
                            }
                            break;
                        }
                    }
                } else {
                    // Le fichier existe au bon endroit, mettre à jour la référence si nécessaire
                    if ($media->file_path !== 'media/' . $filename) {
                        $media->file_path = 'media/' . $filename;
                        $media->save();
                        $result['status'] = 'fixed';
                        $result['message'] = 'Référence dans la base de données corrigée';
                    } else {
                        $result['status'] = 'ok';
                        $result['message'] = 'Fichier correctement référencé';
                    }
                }
                break;
            }
        }
        
        return response()->json($result);
    }

    /**
     * Vérifier et créer le lien symbolique storage si nécessaire
     */
    public function ensureStorageLink()
    {
        $publicPath = public_path('storage');
        $storagePath = storage_path('app/public');
        
        $result = [
            'success' => true,
            'message' => '',
            'public_path' => $publicPath,
            'storage_path' => $storagePath,
            'link_exists' => false,
            'directories_created' => []
        ];
        
        // Vérifier si le lien symbolique existe
        if (file_exists($publicPath) && is_link($publicPath)) {
            $result['link_exists'] = true;
            $result['message'] = 'Le lien symbolique existe déjà.';
        } else {
            // Supprimer le dossier s'il existe mais n'est pas un lien symbolique
            if (file_exists($publicPath) && !is_link($publicPath)) {
                if (is_dir($publicPath)) {
                    // Sauvegarde des fichiers existants si nécessaire
                    $backupPath = storage_path('app/public_backup_' . time());
                    File::copyDirectory($publicPath, $backupPath);
                    $result['backup_created'] = $backupPath;
                }
                // Supprimer le dossier
                File::deleteDirectory($publicPath);
            }
            
            // Créer le lien symbolique
            try {
                if (!file_exists($storagePath)) {
                    mkdir($storagePath, 0755, true);
                    $result['directories_created'][] = $storagePath;
                }
                
                // Créer les sous-répertoires nécessaires
                $requiredDirs = ['media', 'news', 'documents', 'programs'];
                foreach ($requiredDirs as $dir) {
                    if (!file_exists($storagePath . '/' . $dir)) {
                        mkdir($storagePath . '/' . $dir, 0755, true);
                        $result['directories_created'][] = $storagePath . '/' . $dir;
                    }
                }
                
                // Créer le lien symbolique
                if (function_exists('symlink')) {
                    symlink($storagePath, $publicPath);
                    $result['message'] = 'Lien symbolique créé avec succès.';
                    $result['link_created'] = true;
                } else {
                    $result['success'] = false;
                    $result['message'] = 'La fonction symlink n\'est pas disponible sur ce système.';
                    $result['error'] = 'symlink_function_not_available';
                }
            } catch (\Exception $e) {
                $result['success'] = false;
                $result['message'] = 'Erreur lors de la création du lien symbolique: ' . $e->getMessage();
                $result['error'] = $e->getMessage();
            }
        }
        
        return response()->json($result);
    }

    /**
     * Scanner un dossier dans storage et ajouter les fichiers à la base de données
     */
    public function scanDirectory(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'directory' => 'required|string',
            'type' => 'required|in:photo,video,all'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $directory = $request->directory;
        $type = $request->type;
        $categoryId = $request->category_id;
        
        // S'assurer que le répertoire existe
        $fullPath = storage_path('app/public/' . $directory);
        if (!file_exists($fullPath) || !is_dir($fullPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Le répertoire spécifié n\'existe pas'
            ], 404);
        }

        // Récupérer tous les fichiers dans le répertoire
        $files = File::files($fullPath);
        
        $processed = [];
        $errors = [];
        $added = [];
        
        foreach ($files as $file) {
            $filename = $file->getFilename();
            $extension = strtolower($file->getExtension());
            $relativePath = $directory . '/' . $filename;
            
            // Déterminer le type de fichier
            $fileType = null;
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $fileType = 'photo';
            } elseif (in_array($extension, ['mp4', 'webm', 'avi', 'mov'])) {
                $fileType = 'video';
            }
            
            // Vérifier si on doit traiter ce type de fichier
            if ($fileType && ($type === 'all' || $type === $fileType)) {
                // Vérifier si le fichier existe déjà dans la base de données
                $exists = Media::where('file_path', $relativePath)->exists();
                
                if (!$exists) {
                    try {
                        // Créer un nouveau média
                        $media = new Media();
                        $media->title = pathinfo($filename, PATHINFO_FILENAME); // Nom du fichier sans extension
                        $media->type = $fileType;
                        $media->file_path = $relativePath;
                        if ($categoryId) {
                            $media->category_id = $categoryId;
                        }
                        $media->save();
                        
                        $added[] = [
                            'id' => $media->id,
                            'title' => $media->title,
                            'type' => $fileType,
                            'file_path' => $relativePath,
                            'url' => asset('storage/' . $relativePath)
                        ];
                    } catch (\Exception $e) {
                        $errors[] = [
                            'file' => $filename,
                            'error' => $e->getMessage()
                        ];
                    }
                } else {
                    $processed[] = [
                        'file' => $filename,
                        'status' => 'already_exists'
                    ];
                }
            } else {
                $processed[] = [
                    'file' => $filename, 
                    'status' => 'skipped',
                    'reason' => 'file_type_not_supported'
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => count($added) . ' fichiers ajoutés à la base de données',
            'added' => $added,
            'processed' => $processed,
            'errors' => $errors,
            'directory' => $directory,
            'full_path' => $fullPath
        ]);
    }

    /**
     * Créer un nouveau répertoire dans storage
     */
    public function createDirectory(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'directory' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $directory = trim($request->directory, '/');
        
        // Sécurité: empêcher la création de répertoires en dehors de storage/app/public
        if (strpos($directory, '..') !== false) {
            return response()->json([
                'success' => false,
                'message' => 'Chemin de répertoire invalide'
            ], 400);
        }
        
        // Créer le répertoire dans storage/app/public
        try {
            $result = Storage::disk('public')->makeDirectory($directory);
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Répertoire créé avec succès',
                    'directory' => $directory,
                    'full_path' => storage_path('app/public/' . $directory)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de créer le répertoire'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du répertoire: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister les répertoires et fichiers dans storage/app/public
     */
    public function listDirectories(Request $request)
    {
        // Valider le paramètre de chemin
        $path = $request->query('path', '');
        
        // Sécurité: empêcher l'accès en dehors de storage/app/public
        if (strpos($path, '..') !== false) {
            return response()->json([
                'success' => false,
                'message' => 'Chemin invalide'
            ], 400);
        }

        // Récupérer les répertoires
        $directories = Storage::disk('public')->directories($path);
        
        // Récupérer les fichiers
        $filesInfo = [];
        $files = Storage::disk('public')->files($path);
        
        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            // Déterminer le type de fichier
            $type = 'other';
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $type = 'photo';
            } elseif (in_array($extension, ['mp4', 'webm', 'avi', 'mov'])) {
                $type = 'video';
            }
            
            // Vérifier si le fichier est déjà dans la base de données
            $inDatabase = Media::where('file_path', $file)->exists();
            
            $filesInfo[] = [
                'name' => basename($file),
                'path' => $file,
                'url' => asset('storage/' . $file),
                'type' => $type,
                'size' => Storage::disk('public')->size($file),
                'last_modified' => Storage::disk('public')->lastModified($file),
                'in_database' => $inDatabase
            ];
        }
        
        return response()->json([
            'success' => true,
            'current_path' => $path,
            'directories' => $directories,
            'files' => $filesInfo
        ]);
    }

    /**
     * Ajouter rapidement un média à partir d'un chemin de fichier existant
     */
    public function quickAddMedia(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'type' => 'required|in:photo,video',
            'title' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Normaliser le chemin si nécessaire
        $filePath = $request->file_path;
        
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
        
        // Vérifier si le fichier existe
        $fileExists = Storage::disk('public')->exists($filePath);
        
        if (!$fileExists) {
            return response()->json([
                'success' => false,
                'message' => 'Le fichier spécifié n\'existe pas dans le stockage',
                'file_path' => $filePath
            ], 404);
        }
        
        // Déterminer le type de fichier si non spécifié
        $type = $request->type;
        if ($type === null) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $type = 'photo';
            } elseif (in_array($extension, ['mp4', 'webm', 'avi', 'mov'])) {
                $type = 'video';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de déterminer le type de fichier, veuillez le spécifier explicitement'
                ], 400);
            }
        }
        
        // Utiliser le nom du fichier comme titre si non spécifié
        $title = $request->title ?: pathinfo($filePath, PATHINFO_FILENAME);
        
        // Vérifier si un média avec ce chemin existe déjà
        $existingMedia = Media::where('file_path', $filePath)->first();
        
        if ($existingMedia) {
            return response()->json([
                'success' => false,
                'message' => 'Un média avec ce chemin de fichier existe déjà',
                'media' => $existingMedia
            ], 409);
        }
        
        // Créer le média
        $media = new Media();
        $media->title = $title;
        $media->type = $type;
        $media->file_path = $filePath;
        if ($request->category_id) {
            $media->category_id = $request->category_id;
        }
        $media->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Média ajouté avec succès',
            'media' => $media,
            'file_url' => asset('storage/' . $filePath)
        ]);
    }

    /**
     * Enregistre un fichier média existant dans la base de données
     */
    public function registerExistingMedia(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string',
            'title' => 'nullable|string',
            'type' => 'nullable|in:photo,video',
            'category_id' => 'nullable|exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $filename = $request->filename;
        $mediaPath = 'media/' . $filename;
        
        // Vérifier si le fichier existe
        if (!Storage::disk('public')->exists($mediaPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Le fichier spécifié n\'existe pas dans le stockage',
                'file_path' => $mediaPath
            ], 404);
        }
        
        // Vérifier si un média avec ce chemin existe déjà
        $existingMedia = Media::where('file_path', $mediaPath)->first();
        
        if ($existingMedia) {
            return response()->json([
                'success' => false,
                'message' => 'Un média avec ce chemin de fichier existe déjà',
                'media' => $existingMedia,
                'file_url' => url('storage/' . $mediaPath)
            ], 409);
        }
        
        // Déterminer le type si non spécifié
        $type = $request->type;
        if ($type === null) {
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $type = 'photo';
            } elseif (in_array($extension, ['mp4', 'webm', 'avi', 'mov'])) {
                $type = 'video';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de déterminer le type de fichier, veuillez le spécifier explicitement'
                ], 400);
            }
        }
        
        // Utiliser le nom du fichier comme titre si non spécifié
        $title = $request->title ?: pathinfo($filename, PATHINFO_FILENAME);
        
        // Créer le média
        $media = new Media();
        $media->title = $title;
        $media->type = $type;
        $media->file_path = $mediaPath;
        if ($request->category_id) {
            $media->category_id = $request->category_id;
        }
        $media->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Média enregistré avec succès',
            'media' => $media,
            'file_url' => url('storage/' . $mediaPath)
        ]);
    }
} 