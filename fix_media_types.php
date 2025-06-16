<?php
// Ce script vérifie et corrige les types de médias dans la base de données

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Extensions de fichiers par type
$fileTypes = [
    'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'],
    'video' => ['mp4', 'webm', 'mov', 'avi', '3gp', 'mkv', 'flv', 'wmv'],
    'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv']
];

// Récupérer tous les médias
try {
    // D'abord, vérifier la structure de la table media
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('media');
    echo "Colonnes de la table media: " . implode(', ', $columns) . "\n";
    
    // Si possible, vérifier la définition de la colonne type
    try {
        $connection = \Illuminate\Support\Facades\DB::connection();
        $query = "SHOW COLUMNS FROM media WHERE Field = 'type'";
        $typeInfo = $connection->select($query);
        echo "Définition de la colonne type: " . json_encode($typeInfo) . "\n\n";
    } catch (\Exception $e) {
        echo "Impossible de récupérer les informations de la colonne type: " . $e->getMessage() . "\n\n";
    }
    
    // À la place de la mise à jour par Eloquent, utiliser des requêtes SQL directes
    $medias = \App\Models\Media::all();
    echo "Vérification de {$medias->count()} médias...\n";
    
    $updatedCount = 0;
    
    foreach ($medias as $media) {
        // Ignorer les médias sans chemin de fichier
        if (!$media->file_path) {
            echo "ID {$media->id}: Aucun chemin de fichier défini.\n";
            continue;
        }
        
        // Ignorer les URLs externes
        if (filter_var($media->file_path, FILTER_VALIDATE_URL)) {
            echo "ID {$media->id}: URL externe - {$media->file_path}\n";
            continue;
        }
        
        // Récupérer l'extension du fichier
        $filename = basename($media->file_path);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $extension = strtolower($extension);
        
        // Déterminer le type correct
        $correctType = null;
        foreach ($fileTypes as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                $correctType = $type;
                break;
            }
        }
        
        // Si aucun type n'a été trouvé, conserver le type actuel
        if (!$correctType) {
            echo "ID {$media->id}: Extension inconnue '{$extension}', type actuel conservé: {$media->type}\n";
            continue;
        }
        
        // Définir le mappage des types pour la base de données
        $typeMapping = [
            'image' => 'photo',  // Utiliser 'photo' au lieu de 'image' si c'est ce qui est attendu
            'video' => 'video',
            'document' => 'document'
        ];
        
        $dbType = $typeMapping[$correctType] ?? $correctType;
        
        // Corriger le type si nécessaire
        if ($media->type != $dbType) {
            $oldType = $media->type;
            
            // Utiliser une requête SQL directe pour éviter les problèmes de guillemets
            try {
                \Illuminate\Support\Facades\DB::table('media')
                    ->where('id', $media->id)
                    ->update(['type' => $dbType]);
                
                $updatedCount++;
                echo "ID {$media->id}: Type changé de '{$oldType}' à '{$dbType}' pour le fichier {$filename}\n";
            } catch (\Exception $e) {
                echo "ERREUR mise à jour ID {$media->id}: " . $e->getMessage() . "\n";
            }
        } else {
            echo "ID {$media->id}: Type correct ({$media->type}) pour le fichier {$filename}\n";
        }
    }
    
    echo "\nTerminé ! {$updatedCount} médias ont été mis à jour.\n";
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
} 