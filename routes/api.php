<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\{
    CategoryController,
    CoachController,
    PlayerController,
    ScheduleController,
    RegistrationController,
    MediaController,
    NewsController,
    MatchController,
    ContactController,
    UserController,
    PlayerAuthController,
    CoachAuthController,
    ParentAuthController,
    PasswordResetController,
    UploadController
};

Route::get('/ping', function () {
    return response()->json(['message' => 'API OK']);
});

// Routes API RESTful pour chaque ressource
Route::apiResource('categories', CategoryController::class);
Route::apiResource('coaches', CoachController::class);
Route::apiResource('players', PlayerController::class);
// Route spécifique pour récupérer les joueurs par catégorie
Route::get('/players/category/{categoryId}', [PlayerController::class, 'getPlayersByCategory']);
Route::apiResource('schedules', ScheduleController::class);
Route::apiResource('registrations', RegistrationController::class);
Route::get('detailed-registrations', [RegistrationController::class, 'getDetailedRegistrations']);
// Routes pour gérer les inscriptions en attente
Route::get('pending-registrations', [RegistrationController::class, 'getPendingRegistrations']);
Route::post('registrations/{id}/accept', [RegistrationController::class, 'acceptRegistration']);
Route::post('registrations/{id}/reject', [RegistrationController::class, 'rejectRegistration']);
Route::apiResource('media', MediaController::class);
Route::apiResource('news', NewsController::class);
Route::apiResource('matches', MatchController::class);
Route::apiResource('contacts', ContactController::class);
// Route pour répondre aux contacts
Route::post('contacts/{contact}/respond', [ContactController::class, 'respond']);







// Routes d'authentification générale
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [UserController::class, 'profile']);
    Route::post('/logout', [UserController::class, 'logout']);
});

// Routes d'authentification pour les joueurs
Route::post('/player/check-and-register', [PlayerAuthController::class, 'checkPlayerAndRegister']);
Route::post('/player/login', [PlayerAuthController::class, 'login']);
Route::post('/player/check-registration', [PlayerAuthController::class, 'checkRegistration']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/player/profile', [PlayerAuthController::class, 'profile']);
    Route::post('/player/logout', [PlayerAuthController::class, 'logout']);
    Route::post('/player/update-document', [PlayerAuthController::class, 'updateDocument']);
    Route::post('/player/add-document', [PlayerAuthController::class, 'addDocument']);
});

// Routes d'authentification pour les coachs
Route::post('/coach/register', [CoachAuthController::class, 'register']);
Route::post('/coach/login', [CoachAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/coach/profile', [CoachAuthController::class, 'profile']);
    Route::post('/coach/logout', [CoachAuthController::class, 'logout']);
});

// Routes d'authentification pour les parents
Route::post('/parent/register', [ParentAuthController::class, 'register']);
Route::post('/parent/login', [ParentAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/parent/profile', [ParentAuthController::class, 'profile']);
    Route::post('/parent/logout', [ParentAuthController::class, 'logout']);
});

// Routes pour la ru00e9initialisation du mot de passe
Route::post('/password/email', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [PasswordResetController::class, 'reset']);
// Route pour générer un lien de réinitialisation sans envoi d'email (développement uniquement)
Route::get('/password/generate-link', [PasswordResetController::class, 'generateResetLink']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin-only', function () {
        return response()->json(['message' => 'Bienvenue Admin !']);
    });
});

Route::post('/create-admin', [UserController::class, 'createAdmin']);

Route::middleware('auth:sanctum')->get('/profile', [UserController::class, 'profile']);





Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes spécifiques pour les actualités et événements
Route::get('/events', [NewsController::class, 'getEvents']);
Route::get('/news-only', [NewsController::class, 'getNews']);

// Routes spécifiques pour les médias
Route::get('/photos', [MediaController::class, 'getPhotos']);
Route::get('/videos', [MediaController::class, 'getVideos']);
Route::get('/media', [MediaController::class, 'index']);
Route::get('/media/category/{categoryId}', [MediaController::class, 'getByCategory']);
Route::get('/media/{media}', [MediaController::class, 'show']);
Route::post('/media', [MediaController::class, 'store'])->middleware(['auth:sanctum', 'admin']);
Route::put('/media/{id}', [MediaController::class, 'update'])->middleware(['auth:sanctum', 'admin']);
Route::delete('/media/{media}', [MediaController::class, 'destroy'])->middleware(['auth:sanctum', 'admin']);
Route::post('/media/migrate-to-cloudinary', [MediaController::class, 'migrateToCloudinary'])->middleware(['auth:sanctum', 'admin']);

// Routes pour l'administration des actualités/événements
Route::prefix('admin')->group(function () {
    // Routes admin pour News
    Route::get('/news', [App\Http\Controllers\Admin\NewsController::class, 'index']);
    Route::post('/news', [App\Http\Controllers\Admin\NewsController::class, 'store']);
    Route::get('/news/{id}', [App\Http\Controllers\Admin\NewsController::class, 'show']);
    Route::put('/news/{id}', [App\Http\Controllers\Admin\NewsController::class, 'update']);
    Route::delete('/news/{id}', [App\Http\Controllers\Admin\NewsController::class, 'destroy']);

    // Routes spécifiques pour la publication et la dépublication
    Route::post('/news/{id}/publish', [App\Http\Controllers\Admin\NewsController::class, 'publish']);
    Route::post('/news/{id}/unpublish', [App\Http\Controllers\Admin\NewsController::class, 'unpublish']);

    // Routes admin pour Media
    Route::get('/media', [App\Http\Controllers\Admin\MediaController::class, 'index']);
    Route::post('/media', [App\Http\Controllers\Admin\MediaController::class, 'store']);
    Route::get('/media/{id}', [App\Http\Controllers\Admin\MediaController::class, 'show']);
    Route::put('/media/{id}', [App\Http\Controllers\Admin\MediaController::class, 'update']);
    Route::delete('/media/{id}', [App\Http\Controllers\Admin\MediaController::class, 'destroy']);
});

// Routes pour les paiements
Route::prefix('payment')->group(function () {
    Route::post('/bank-transfer/create-session', [App\Http\Controllers\PaymentController::class, 'createBankTransferSession']);
    Route::post('/check-status', [App\Http\Controllers\PaymentController::class, 'checkPaymentStatus']);
    Route::post('/webhook', [App\Http\Controllers\PaymentController::class, 'handleWebhook']);
});

// Routes pour les uploads d'images
Route::post('/upload/news-image', [App\Http\Controllers\UploadController::class, 'uploadNewsImage']);
Route::post('/upload/media', [App\Http\Controllers\UploadController::class, 'uploadMedia']);
Route::post('/upload/quick-add-media', [App\Http\Controllers\UploadController::class, 'quickAddMedia']);
Route::post('/upload/register-media', [App\Http\Controllers\UploadController::class, 'registerExistingMedia']);
Route::get('/upload/fix-image-paths', [App\Http\Controllers\UploadController::class, 'fixImagePaths']);
Route::get('/upload/fix-media-paths', [App\Http\Controllers\UploadController::class, 'fixMediaPaths']);
Route::get('/upload/repair-image', [App\Http\Controllers\UploadController::class, 'repairSpecificImage']);
Route::get('/upload/repair-media', [App\Http\Controllers\UploadController::class, 'repairSpecificMedia']);
Route::get('/upload/ensure-storage-link', [App\Http\Controllers\UploadController::class, 'ensureStorageLink']);
Route::get('/upload/list-directories', [App\Http\Controllers\UploadController::class, 'listDirectories']);
Route::post('/upload/create-directory', [App\Http\Controllers\UploadController::class, 'createDirectory']);
Route::post('/upload/scan-directory', [App\Http\Controllers\UploadController::class, 'scanDirectory']);

// Route de test pour lister tous les joueurs avec leur catégorie
Route::get('/test/players', function() {
    return \App\Models\Player::with('category')->get();
});
// Route de test pour lister tous les coachs avec leur catégorie
Route::get('/test/coaches', function() {
    return \App\Models\Coach::with('category')->get();
});

// Routes pour les matchs et entraînements par catégorie
Route::get('/matches/category/{categoryId}', [MatchController::class, 'getMatchesByCategory']);
Route::get('/schedules/category/{categoryId}', [ScheduleController::class, 'getSchedulesByCategory']);

// Routes pour permettre au coach de gérer les entraînements (protégées par authentification)
Route::middleware('auth:sanctum')->group(function () {
    // Gestion des entraînements
    Route::post('/coach/schedules', [ScheduleController::class, 'storeByCoach']);
    Route::put('/coach/schedules/{scheduleId}', [ScheduleController::class, 'updateByCoach']);
    Route::delete('/coach/schedules/{scheduleId}', [ScheduleController::class, 'destroyByCoach']);
    
    // Gestion des matchs
    Route::post('/coach/matches', [MatchController::class, 'storeByCoach']);
    Route::put('/coach/matches/{matchId}', [MatchController::class, 'updateByCoach']);
    Route::delete('/coach/matches/{matchId}', [MatchController::class, 'destroyByCoach']);
});

// Routes pour gérer les performances et cartes jaunes des joueurs
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/players/{playerId}/performance', [PlayerController::class, 'updatePerformance']);
    Route::get('/players/{playerId}/performance', [PlayerController::class, 'getPerformance']);
    Route::post('/players/{playerId}/yellow-card', [PlayerController::class, 'updateYellowCard']);
    Route::get('/players/{playerId}/yellow-cards', [PlayerController::class, 'getYellowCards']);
});

// Route de test pour l'envoi d'email
Route::get('/test-email/{email}', function($email) {
    try {
        \Illuminate\Support\Facades\Mail::raw('Test d\'envoi d\'email depuis ACOS Football Academy', function($message) use ($email) {
            $message->to($email)
                    ->subject('Test d\'envoi d\'email');
        });
        
        return response()->json([
            'success' => true,
            'message' => 'Email de test envoyé avec succès à ' . $email
        ]);
    } catch (\Exception $e) {
        \Log::error('Erreur lors du test d\'envoi d\'email', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'envoi de l\'email de test',
            'error' => $e->getMessage()
        ], 500);
    }
});
