<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Media;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class DiagnosticController extends Controller
{
    public function checkCloudinaryConfig()
    {
        $result = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'cloudinary_config' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME', 'Non défini'),
                'api_key' => env('CLOUDINARY_KEY') ? substr(env('CLOUDINARY_KEY'), 0, 3) . '...' : 'Non défini',
                'api_secret' => env('CLOUDINARY_SECRET') ? 'Défini (masqué)' : 'Non défini',
                'cloudinary_url' => env('CLOUDINARY_URL') ? substr(env('CLOUDINARY_URL'), 0, 15) . '...' : 'Non défini'
            ],
            'cloudinary_instance' => null,
            'test_upload' => null
        ];

        // Vérifier si l'instance Cloudinary peut être créée
        try {
            $cloudinary = app('cloudinary');
            $result['cloudinary_instance'] = 'OK - Instance créée avec succès';
        } catch (\Exception $e) {
            $result['cloudinary_instance'] = 'ERREUR - ' . $e->getMessage();
            // Retourner immédiatement car les tests suivants échoueront
            return response()->json($result);
        }

        // Tester un upload vers Cloudinary
        try {
            // Créer une petite image test
            $tempFile = tempnam(sys_get_temp_dir(), 'cloudinary_test_');
            $image = imagecreatetruecolor(10, 10);
            $white = imagecolorallocate($image, 255, 255, 255);
            imagefill($image, 0, 0, $white);
            imagepng($image, $tempFile);

            // Tenter l'upload
            $testFolder = "acos_football/test";
            $testPublicId = "diagnostic_test_" . time();
            
            $uploadResult = cloudinary()->upload($tempFile, [
                'folder' => $testFolder,
                'public_id' => $testPublicId
            ]);
            
            $result['test_upload'] = [
                'status' => 'OK',
                'url' => $uploadResult->getSecurePath(),
                'public_id' => $uploadResult->getPublicId()
            ];
            
            // Supprimer l'image test
            cloudinary()->destroy($uploadResult->getPublicId());
            
        } catch (\Exception $e) {
            $result['test_upload'] = [
                'status' => 'ERREUR',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }
        
        return response()->json($result);
    }
    
    public function setupCloudinaryConfig(Request $request)
    {
        try {
            $cloudName = $request->input('cloud_name');
            $apiKey = $request->input('api_key');
            $apiSecret = $request->input('api_secret');
            
            if (!$cloudName || !$apiKey || !$apiSecret) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tous les paramètres sont requis'
                ], 400);
            }
            
            // Mettre à jour les variables d'environnement pour cette requête
            putenv("CLOUDINARY_CLOUD_NAME={$cloudName}");
            putenv("CLOUDINARY_KEY={$apiKey}");
            putenv("CLOUDINARY_SECRET={$apiSecret}");
            putenv("CLOUDINARY_URL=cloudinary://{$apiKey}:{$apiSecret}@{$cloudName}");
            
            // Essayer d'utiliser cette configuration
            $testResult = $this->testCloudinaryConfig();
            
            if ($testResult['success']) {
                // Stocker ces valeurs dans la base de données
                $this->storeCloudinaryConfigInDb($cloudName, $apiKey, $apiSecret);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Configuration Cloudinary mise à jour avec succès',
                    'test_result' => $testResult
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'La configuration semble invalide',
                    'test_result' => $testResult
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la configuration: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    
    private function testCloudinaryConfig()
    {
        try {
            // Tenter de créer une instance Cloudinary
            $cloudinary = app('cloudinary');
            
            // Créer une petite image test
            $tempFile = tempnam(sys_get_temp_dir(), 'cloudinary_test_');
            $image = imagecreatetruecolor(10, 10);
            $white = imagecolorallocate($image, 255, 255, 255);
            imagefill($image, 0, 0, $white);
            imagepng($image, $tempFile);
            
            // Tenter l'upload
            $testFolder = "acos_football/test";
            $testPublicId = "config_test_" . time();
            
            $uploadResult = cloudinary()->upload($tempFile, [
                'folder' => $testFolder,
                'public_id' => $testPublicId
            ]);
            
            // Nettoyage
            unlink($tempFile);
            cloudinary()->destroy($uploadResult->getPublicId());
            
            return [
                'success' => true,
                'message' => 'Test réussi',
                'url' => $uploadResult->getSecurePath()
            ];
        } catch (\Exception $e) {
            if (file_exists($tempFile ?? '')) {
                unlink($tempFile);
            }
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }
    }
    
    private function storeCloudinaryConfigInDb($cloudName, $apiKey, $apiSecret)
    {
        // Créer la table settings si elle n'existe pas
        if (!\Schema::hasTable('settings')) {
            \Schema::create('settings', function ($table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
        
        // Stocker les valeurs
        \DB::table('settings')->updateOrInsert(
            ['key' => 'cloudinary_cloud_name'],
            ['value' => $cloudName, 'updated_at' => now()]
        );
        
        \DB::table('settings')->updateOrInsert(
            ['key' => 'cloudinary_key'],
            ['value' => $apiKey, 'updated_at' => now()]
        );
        
        \DB::table('settings')->updateOrInsert(
            ['key' => 'cloudinary_secret'],
            ['value' => $apiSecret, 'updated_at' => now()]
        );
        
        \DB::table('settings')->updateOrInsert(
            ['key' => 'cloudinary_url'],
            ['value' => "cloudinary://{$apiKey}:{$apiSecret}@{$cloudName}", 'updated_at' => now()]
        );
    }
    
    public function configurationForm()
    {
        return view('cloudinary-setup');
    }
} 