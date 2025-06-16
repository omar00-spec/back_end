<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Player;
use App\Models\Registration;

class ParentAuthController extends Controller
{
    /**
     * Enregistrer un nouveau parent
     */
    public function register(Request $request)
    {
        try {
            // Journaliser la requête reçue
            Log::info('Requête d\'enregistrement parent reçue', [
                'data' => $request->all()
            ]);
            
            // Valider les données
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|string|email|unique:users,email',
                'phone' => 'required|string',
                'player_id' => 'nullable|integer|exists:players,id',
            ]);

            if ($validator->fails()) {
                Log::error('Erreur de validation', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation: ' . implode(', ', $validator->errors()->all()),
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier si l'email est déjà associé à une inscription
            $registration = Registration::where('parent_email', $request->email)->first();
            
            if (!$registration && $request->player_id) {
                // Si aucune inscription n'est trouvée mais que l'ID du joueur est fourni,
                // vérifier si le joueur existe et mettre à jour l'inscription
                $player = Player::find($request->player_id);
                if ($player) {
                    $registration = Registration::where('player_id', $player->id)->first();
                    if ($registration) {
                        $registration->parent_email = $request->email;
                        $registration->parent_name = $request->name;
                        $registration->parent_phone = $request->phone;
                        $registration->save();
                    }
                }
            }

            // Générer un mot de passe aléatoire
            $password = Str::random(10);
            
            // Créer un utilisateur pour ce parent
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($password),
                'role' => 'parent',
            ]);

            Log::info('Parent enregistré avec succès', [
                'user_id' => $user->id,
                'registration_id' => $registration ? $registration->id : null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Parent enregistré avec succès',
                'password' => $password,
                'user' => $user,
                'registration' => $registration
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement du parent', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connexion d'un parent
     */
    public function login(Request $request)
    {
        // Journaliser la tentative de connexion
        Log::info('Tentative de connexion parent', [
            'email' => $request->email
        ]);

        $fields = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Vérifier l'email
        $user = User::where('email', $fields['email'])->first();

        // Journaliser les informations de l'utilisateur trouvé
        if ($user) {
            Log::info('Utilisateur trouvé', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);
        } else {
            Log::warning('Utilisateur non trouvé', [
                'email' => $fields['email']
            ]);
        }

        // Vérifier si l'utilisateur existe et si c'est un parent
        if (!$user || $user->role !== 'parent' || !Hash::check($fields['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides ou vous n\'êtes pas un parent'
            ], 401);
        }

        // Trouver les inscriptions où cet email est utilisé comme email parent
        $registrations = Registration::where('parent_email', $user->email)->get();
        
        if ($registrations->isEmpty()) {
            Log::warning('Aucune inscription trouvée pour ce parent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Aucun enfant associé à votre compte n\'a été trouvé'
            ], 401);
        }

        // Récupérer les joueurs associés à ces inscriptions
        $playerIds = $registrations->pluck('player_id')->filter()->toArray();
        $players = Player::with('category')->whereIn('id', $playerIds)->get();
        
        // Créer un token
        $token = $user->createToken('parenttoken')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'registrations' => $registrations,
            'players' => $players,
            'token' => $token
        ], 200);
    }

    /**
     * Profil du parent (sécurisé)
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        // Vérifier si c'est un parent
        if ($user->role !== 'parent') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        // Trouver les inscriptions où cet email est utilisé comme email parent
        $registrations = Registration::where('parent_email', $user->email)->get();
        
        // Récupérer les joueurs associés à ces inscriptions avec leurs informations complètes
        $playerIds = $registrations->pluck('player_id')->filter()->toArray();
        
        // Récupérer les catégories des joueurs pour le planning des entraînements
        $categoryIds = [];
        $players = Player::with(['category', 'performance'])
            ->whereIn('id', $playerIds)
            ->get();
            
        foreach ($players as $player) {
            if ($player->category_id) {
                $categoryIds[] = $player->category_id;
            }
        }
        
        // Récupérer les plannings d'entraînement pour les catégories des enfants
        $schedules = \App\Models\Schedule::whereIn('category_id', $categoryIds)->get();
        
        // Récupérer les matchs pour les catégories des enfants
        $matches = \App\Models\MatchModel::whereIn('category_id', $categoryIds)->get();
        
        // Récupérer les documents associés aux joueurs
        $documents = [];
        foreach ($registrations as $registration) {
            if ($registration->documents) {
                $docs = json_decode($registration->documents, true);
                if (is_array($docs)) {
                    foreach ($docs as $docType => $docPath) {
                        $documents[] = [
                            'player_id' => $registration->player_id,
                            'type' => $docType,
                            'path' => $docPath,
                            'name' => $this->getDocumentName($docType)
                        ];
                    }
                }
            }
        }
        
        // Charger les joueurs avec leurs catégories et toutes les informations détaillées
        $formattedPlayers = $players->map(function ($player) {
            return [
                'id' => $player->id,
                'name' => $player->firstname . ' ' . $player->lastname,
                'firstname' => $player->firstname,
                'lastname' => $player->lastname,
                'birth_date' => $player->birth_date,
                'photo' => $player->photo,
                'team' => $player->team,
                'position' => $player->team, // Utilisé comme position dans l'interface
                'category_id' => $player->category_id,
                'category' => $player->category,
                'yellow_cards' => $player->yellow_cards ?? 0,
                'license_number' => $player->license_number ?? null,
                'performance' => $player->performance,
                // Ajout d'autres champs pertinents
                'created_at' => $player->created_at,
                'updated_at' => $player->updated_at
            ];
        });

        return response()->json([
            'success' => true,
            'user' => $user,
            'registrations' => $registrations,
            'players' => $formattedPlayers,
            'schedules' => $schedules,
            'matches' => $matches,
            'documents' => $documents
        ], 200);
    }
    
    /**
     * Obtenir le nom lisible d'un type de document
     * 
     * @param string $docType
     * @return string
     */
    private function getDocumentName($docType)
    {
        $documentNames = [
            'identity_card' => 'Carte d\'identité',
            'birth_certificate' => 'Acte de naissance',
            'medical_certificate' => 'Certificat médical',
            'photo' => 'Photo d\'identité',
            'license' => 'Licence sportive',
            'payment_receipt' => 'Reçu de paiement',
            'registration_form' => 'Formulaire d\'inscription',
            'authorization' => 'Autorisation parentale'
        ];
        
        return $documentNames[$docType] ?? ucfirst(str_replace('_', ' ', $docType));
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        // Supprimer le token qui a été utilisé pour l'authentification
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }
} 