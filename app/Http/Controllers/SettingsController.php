<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Exception\ApiError;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class SettingsController extends Controller
{
    /**
     * Get Cloudinary settings
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCloudinarySettings()
    {
        try {
            // Essayer de récupérer les paramètres depuis la base de données
            // On utilise la table 'settings' avec des clés et des valeurs
            $settings = DB::table('settings')
                ->whereIn('key', ['cloudinary_cloud_name', 'cloudinary_key'])
                ->get()
                ->keyBy('key')
                ->map(function ($item) {
                    return $item->value;
                })
                ->toArray();
            
            return response()->json([
                'cloudName' => $settings['cloudinary_cloud_name'] ?? '',
                'apiKey' => $settings['cloudinary_key'] ?? '',
                // Ne jamais renvoyer la clé secrète, juste un indicateur qu'elle existe
                'hasApiSecret' => isset($settings['cloudinary_secret']) && !empty($settings['cloudinary_secret']),
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des paramètres Cloudinary', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Erreur lors de la récupération des paramètres',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Save Cloudinary settings
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveCloudinarySettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'cloudName' => 'required|string',
                'apiKey' => 'required|string',
                'apiSecret' => 'required|string',
            ]);
            
            // Mise à jour des variables d'environnement temporaires (juste pour la requête actuelle)
            putenv("CLOUDINARY_CLOUD_NAME={$validated['cloudName']}");
            putenv("CLOUDINARY_KEY={$validated['apiKey']}");
            putenv("CLOUDINARY_SECRET={$validated['apiSecret']}");
            putenv("CLOUDINARY_URL=cloudinary://{$validated['apiKey']}:{$validated['apiSecret']}@{$validated['cloudName']}");
            
            // Vérifier si la table 'settings' existe
            if (!Schema::hasTable('settings')) {
                // Créer la table si elle n'existe pas
                Schema::create('settings', function ($table) {
                    $table->id();
                    $table->string('key')->unique();
                    $table->text('value')->nullable();
                    $table->timestamps();
                });
            }
            
            // Enregistrer les paramètres dans la base de données
            foreach ([
                'cloudinary_cloud_name' => $validated['cloudName'],
                'cloudinary_key' => $validated['apiKey'],
                'cloudinary_secret' => $validated['apiSecret'],
                'cloudinary_url' => "cloudinary://{$validated['apiKey']}:{$validated['apiSecret']}@{$validated['cloudName']}",
            ] as $key => $value) {
                DB::table('settings')->updateOrInsert(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'updated_at' => now(),
                    ]
                );
            }
            
            return response()->json([
                'message' => 'Configuration Cloudinary enregistrée avec succès',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation des données échouée',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement des paramètres Cloudinary', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Erreur lors de l\'enregistrement des paramètres',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Test Cloudinary connection
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testCloudinaryConnection(Request $request)
    {
        try {
            $validated = $request->validate([
                'cloudName' => 'required|string',
                'apiKey' => 'required|string',
                'apiSecret' => 'required|string',
            ]);
            
            // Mise à jour des variables d'environnement temporaires (juste pour la requête actuelle)
            putenv("CLOUDINARY_CLOUD_NAME={$validated['cloudName']}");
            putenv("CLOUDINARY_KEY={$validated['apiKey']}");
            putenv("CLOUDINARY_SECRET={$validated['apiSecret']}");
            putenv("CLOUDINARY_URL=cloudinary://{$validated['apiKey']}:{$validated['apiSecret']}@{$validated['cloudName']}");
            
            // Tester la connexion avec Cloudinary en récupérant des informations sur le compte
            try {
                // Créer une image test temporaire
                $tempFile = tempnam(sys_get_temp_dir(), 'cloudinary_test_');
                $image = imagecreatetruecolor(100, 100);
                imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
                imagepng($image, $tempFile);
                
                // Upload test sur Cloudinary
                $result = cloudinary()->upload($tempFile, [
                    'folder' => 'tests',
                    'public_id' => 'test_connection_' . time(),
                ]);
                
                // Supprimer le fichier test sur Cloudinary
                cloudinary()->destroy($result->getPublicId());
                
                // Nettoyer le fichier temporaire
                @unlink($tempFile);
                
                return response()->json([
                    'message' => 'Connexion à Cloudinary établie avec succès',
                    'status' => 'success',
                ]);
            } catch (ApiError $e) {
                Log::error('Erreur API Cloudinary lors du test de connexion', [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
                
                return response()->json([
                    'message' => 'Erreur de connexion à Cloudinary: ' . $e->getMessage(),
                    'status' => 'error',
                ], 400);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation des données échouée',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors du test de connexion Cloudinary', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Erreur lors du test de connexion: ' . $e->getMessage(),
                'status' => 'error',
            ], 500);
        }
    }
} 