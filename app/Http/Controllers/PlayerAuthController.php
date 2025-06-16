<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Player;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Builder;

class PlayerAuthController extends Controller
{
    /**
     * Vérifier si un joueur existe et créer un compte utilisateur
     */
    public function checkPlayerAndRegister(Request $request)
    {
        try {
            // Journaliser la requête reçue
            Log::info('Requête reçue', [
                'data' => $request->all()
            ]);
            
            // Valider les données
            $validator = Validator::make($request->all(), [
                'firstname' => 'required|string',
                'lastname' => 'required|string',
                'email' => 'required|string|email',
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

            // Vérifier si le joueur existe dans la table player
            Log::info('Recherche de joueur', [
                'firstname' => $request->firstname,
                'lastname' => $request->lastname
            ]);
            
            // Recherche plus flexible du joueur
            $player = Player::where(function($query) use ($request) {
                $this->applyNameSearchConditions($query, $request->firstname, $request->lastname);
            })->first();
            
            // Si le joueur n'est pas trouvé, chercher dans la table registration
            if (!$player) {
                Log::warning('Joueur non trouvé dans la table player, recherche dans les inscriptions', [
                    'firstname' => $request->firstname,
                    'lastname' => $request->lastname
                ]);
                
                // Chercher dans les inscriptions
                $registration = Registration::where(function($query) use ($request) {
                    $this->applyNameSearchConditions($query, $request->firstname, $request->lastname, 'player_firstname', 'player_lastname');
                })->first();
                
                if ($registration) {
                    Log::info('Inscription trouvée', [
                        'registration_id' => $registration->id,
                        'player_firstname' => $registration->player_firstname,
                        'player_lastname' => $registration->player_lastname
                    ]);
                    
                    // Vérifier si un joueur est déjà associé
                    if ($registration->player_id) {
                        $player = Player::find($registration->player_id);
                    }
                    
                    // Si pas de joueur associé, créer un nouveau joueur
                    if (!$player) {
                        $player = Player::create([
                            'firstname' => $registration->player_firstname,
                            'lastname' => $registration->player_lastname,
                            'birth_date' => $registration->birth_date,
                            'category_id' => $registration->category_id
                        ]);
                        
                        // Associer le joueur à l'inscription
                        $registration->player_id = $player->id;
                        $registration->save();
                        
                        Log::info('Nouveau joueur créé et associé à l\'inscription', [
                            'player_id' => $player->id,
                            'registration_id' => $registration->id
                        ]);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous n\'êtes pas enregistré comme joueur dans notre académie.'
                    ], 404);
                }
            }

            // Vérifier si ce joueur a une inscription avec un email
            $registration = Registration::where('player_id', $player->id)
                ->first();
                
            if (!$registration) {
                Log::warning('Aucune inscription trouvée pour ce joueur, recherche par nom/prénom', [
                    'player_id' => $player->id
                ]);
                
                // Chercher une inscription avec le même nom/prénom
                $registration = Registration::where(function($query) use ($player) {
                    $this->applyNameSearchConditions($query, $player->firstname, $player->lastname, 'player_firstname', 'player_lastname');
                })->first();
                    
                if ($registration) {
                    // Associer cette inscription au joueur
                    $registration->player_id = $player->id;
                    $registration->save();
                    
                    Log::info('Inscription trouvée et associée au joueur', [
                        'registration_id' => $registration->id,
                        'player_id' => $player->id
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucune inscription trouvée pour ce joueur.'
                    ], 404);
                }
            }
            
            // Si l'inscription n'a pas d'email, utiliser celui fourni
            if (!$registration->player_email) {
                $registration->player_email = $request->email;
                $registration->save();
                
                Log::info('Email ajouté à l\'inscription', [
                    'registration_id' => $registration->id,
                    'email' => $request->email
                ]);
            }
            
            // Vérifier si un utilisateur existe déjà avec cet email
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                // Si l'utilisateur existe mais n'a pas de player_id, l'associer
                if (!$existingUser->player_id) {
                    $existingUser->player_id = $player->id;
                    $existingUser->save();
                    
                    Log::info('Utilisateur existant associé au joueur', [
                        'user_id' => $existingUser->id,
                        'player_id' => $player->id
                    ]);
                    
                    // Générer un token d'authentification
                    $token = $existingUser->createToken('playertoken')->plainTextToken;
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Compte associé avec succès',
                        'user' => $existingUser,
                        'token' => $token
                    ], 200);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Un compte avec cette adresse email existe déjà. Veuillez vous connecter.'
                ], 409);
            }

            // Générer un mot de passe aléatoire
            $password = Str::random(10);

            // Créer un compte utilisateur avec l'email fourni
            $user = User::create([
                'name' => $player->firstname . ' ' . $player->lastname,
                'email' => $request->email,
                'password' => bcrypt($password),
                'role' => 'player',
                'player_id' => $player->id
            ]);

            // Mettre à jour l'email dans l'inscription si différent
            if ($registration->player_email !== $request->email) {
                $registration->player_email = $request->email;
                $registration->save();
                
                Log::info('Email mis à jour dans l\'inscription', [
                    'registration_id' => $registration->id,
                    'old_email' => $registration->player_email,
                    'new_email' => $request->email
                ]);
            }

            // Générer un token d'authentification
            $token = $user->createToken('playertoken')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Compte créé avec succès',
                'user' => $user,
                'password' => $password, // Envoyer le mot de passe généré (dans un vrai environnement, envoyez-le par email)
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du compte', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du compte: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Applique les conditions de recherche par nom et prénom
     * 
     * @param Builder $query
     * @param string $firstname
     * @param string $lastname
     * @param string $firstnameField
     * @param string $lastnameField
     * @return void
     */
    private function applyNameSearchConditions($query, $firstname, $lastname, $firstnameField = 'firstname', $lastnameField = 'lastname')
    {
        // Recherche exacte
        $query->where(function($q) use ($firstname, $lastname, $firstnameField, $lastnameField) {
            $q->where($firstnameField, $firstname)
              ->where($lastnameField, $lastname);
        })
        // Recherche insensible à la casse
        ->orWhere(function($q) use ($firstname, $lastname, $firstnameField, $lastnameField) {
            $q->whereRaw("LOWER($firstnameField) = ?", [strtolower($firstname)])
              ->whereRaw("LOWER($lastnameField) = ?", [strtolower($lastname)]);
        })
        // Recherche avec LIKE pour plus de flexibilité
        ->orWhere(function($q) use ($firstname, $lastname, $firstnameField, $lastnameField) {
            $q->where($firstnameField, 'LIKE', '%' . $firstname . '%')
              ->where($lastnameField, 'LIKE', '%' . $lastname . '%');
        });
    }

    /**
     * Connexion d'un joueur
     */
    public function login(Request $request)
    {
        // Journaliser la tentative de connexion
        Log::info('Tentative de connexion joueur', [
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
                'role' => $user->role,
                'player_id' => $user->player_id
            ]);
        } else {
            Log::warning('Utilisateur non trouvé', [
                'email' => $fields['email']
            ]);
        }

        // Vérifier si l'utilisateur existe et si c'est un joueur
        if (!$user || $user->role !== 'player' || !Hash::check($fields['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides ou vous n\'êtes pas un joueur'
            ], 401);
        }

        // Vérifier si le joueur existe toujours dans la table player
        $player = Player::find($user->player_id);
        if (!$player) {
            Log::warning('Profil joueur non trouvé', [
                'user_id' => $user->id,
                'player_id' => $user->player_id
            ]);
            
            // Vérifier s'il existe un joueur avec le même nom/prénom
            $playerByName = Player::where('firstname', 'like', '%' . explode(' ', $user->name)[0] . '%')
                ->where('lastname', 'like', '%' . explode(' ', $user->name)[1] . '%')
                ->first();
                
            if ($playerByName) {
                Log::info('Joueur trouvé par nom/prénom', [
                    'player_id' => $playerByName->id,
                    'firstname' => $playerByName->firstname,
                    'lastname' => $playerByName->lastname
                ]);
                
                // Mettre à jour l'ID du joueur dans l'utilisateur
                $user->player_id = $playerByName->id;
                $user->save();
                
                $player = $playerByName;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre profil de joueur n\'existe plus'
                ], 401);
            }
        }

        // Vérifier que l'email de l'utilisateur correspond à celui dans la table registration
        $registration = \App\Models\Registration::where('player_id', $player->id)->first();
        
        if (!$registration) {
            Log::warning('Aucune inscription trouvée pour ce joueur', [
                'player_id' => $player->id
            ]);
            
            // Chercher une inscription avec le même nom/prénom
            $registrationByName = \App\Models\Registration::where('player_firstname', 'like', '%' . $player->firstname . '%')
                ->where('player_lastname', 'like', '%' . $player->lastname . '%')
                ->first();
                
            if ($registrationByName) {
                Log::info('Inscription trouvée par nom/prénom', [
                    'registration_id' => $registrationByName->id,
                    'player_firstname' => $registrationByName->player_firstname,
                    'player_lastname' => $registrationByName->player_lastname
                ]);
                
                // Associer cette inscription au joueur
                $registrationByName->player_id = $player->id;
                $registrationByName->save();
                
                $registration = $registrationByName;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune inscription trouvée pour votre profil'
                ], 401);
            }
        }
        
        // Assouplir la vérification de l'email
        if ($registration->player_email && $registration->player_email !== $user->email) {
            Log::warning('Email ne correspond pas', [
                'user_email' => $user->email,
                'registration_email' => $registration->player_email
            ]);
            
            // Mettre à jour l'email dans l'inscription pour qu'il corresponde à celui de l'utilisateur
            $registration->player_email = $user->email;
            $registration->save();
            
            Log::info('Email d\'inscription mis à jour', [
                'registration_id' => $registration->id,
                'new_email' => $user->email
            ]);
        }

        // Récupérer la catégorie du joueur
        $category = $player->category;

        // Créer un token
        $token = $user->createToken('playertoken')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'player' => $player,
            'category' => $category,
            'registration' => $registration,
            'token' => $token
        ], 200);
    }

    /**
     * Profil du joueur (sécurisé) avec toutes ses données associées
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        // Vérifier si c'est un joueur
        if ($user->role !== 'player') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        // Récupérer les informations du joueur avec toutes les relations
        $player = Player::with([
            'category',
            'registration',
            'category.schedules'
        ])->find($user->player_id);
        
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Profil de joueur non trouvé'
            ], 404);
        }

        // Récupérer les plannings spécifiques à la catégorie du joueur
        $schedules = $player->category ? $player->category->schedules : [];

        // Récupérer les documents fournis lors de l'inscription
        $documents = [];
        $documentsInfo = [];
        
        if ($player->registration && $player->registration->documents) {
            $documents = $player->registration->documents;
            
            // Ajouter des informations sur chaque document
            if (is_array($documents)) {
                foreach ($documents as $key => $path) {
                    $documentsInfo[] = [
                        'key' => $key,
                        'path' => $path,
                        'url' => asset('storage/' . $path),
                        'exists' => Storage::disk('public')->exists($path),
                        'extension' => pathinfo($path, PATHINFO_EXTENSION),
                        'type' => $this->getDocumentType(pathinfo($path, PATHINFO_EXTENSION))
                    ];
                }
            }
        }
        
        // Journaliser les informations sur les documents pour le débogage
        Log::info('Documents du joueur:', [
            'player_id' => $player->id,
            'documents' => $documents,
            'documents_info' => $documentsInfo
        ]);
        
        // Récupérer l'heure d'inscription et les détails d'inscription
        $registrationDate = $player->registration ? $player->registration->created_at : null;
        $registrationDetails = $player->registration;
        
        // Récupérer les matchs de la catégorie du joueur
        $matches = [];
        if ($player->category) {
            $matches = \App\Models\MatchModel::where('category_id', $player->category_id)
                ->orderBy('date', 'desc')
                ->get();
        }

        return response()->json([
            'success' => true,
            'user' => $user,
            'player' => $player,
            'category' => $player->category,
            'schedules' => $schedules,
            'documents' => $documents,
            'documents_info' => $documentsInfo,
            'registration_date' => $registrationDate,
            'registration_details' => $registrationDetails,
            'matches' => $matches,
            'storage_path' => asset('storage/')
        ]);
    }

    /**
     * Déterminer le type de document en fonction de l'extension
     */
    private function getDocumentType($extension)
    {
        $extension = strtolower($extension);
        
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            return 'image';
        } elseif ($extension === 'pdf') {
            return 'pdf';
        } else {
            return 'document';
        }
    }
    
    /**
     * Mettre à jour un document du joueur
     */
    public function updateDocument(Request $request)
    {
        try {
            // Valider les données
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'document_key' => 'required|string',
                'player_id' => 'required|exists:players,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier que l'utilisateur connecté est bien le propriétaire du document
            $user = $request->user();
            if ($user->role !== 'player' || $user->player_id != $request->player_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas l\'autorisation de modifier ce document'
                ], 403);
            }

            // Récupérer le joueur et son inscription
            $player = \App\Models\Player::with('registration')->find($request->player_id);
            if (!$player || !$player->registration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscription non trouvée'
                ], 404);
            }

            // Récupérer les documents actuels
            $documents = $player->registration->documents ?? [];
            
            // Supprimer l'ancien document du stockage s'il existe
            if (isset($documents[$request->document_key])) {
                $oldPath = $documents[$request->document_key];
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                    Log::info('Ancien document supprimé', ['path' => $oldPath]);
                }
            }
            
            // Stocker le nouveau document dans le même répertoire que lors de l'inscription
            $file = $request->file('document');
            $path = $file->store('registrations/' . $player->id, 'public');
            
            // Mettre à jour les documents dans l'inscription
            $documents[$request->document_key] = $path;

            $player->registration->documents = $documents;
            $player->registration->save();

            // Déterminer le type de document
            $extension = $file->getClientOriginalExtension();
            $type = $this->getDocumentType($extension);

            // Journaliser l'action
            Log::info('Document mis à jour', [
                'player_id' => $player->id,
                'document_key' => $request->document_key,
                'path' => $path
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document mis à jour avec succès',
                'path' => $path,
                'url' => asset('storage/' . $path),
                'extension' => $extension,
                'type' => $type
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du document', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajouter un nouveau document pour un joueur
     */
    public function addDocument(Request $request)
    {
        try {
            // Valider les données
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'document_key' => 'required|string',
                'player_id' => 'required|exists:players,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier que l'utilisateur connecté est bien le propriétaire du document
            $user = $request->user();
            if ($user->role !== 'player' || $user->player_id != $request->player_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas l\'autorisation d\'ajouter ce document'
                ], 403);
            }

            // Récupérer le joueur et son inscription
            $player = \App\Models\Player::with('registration')->find($request->player_id);
            if (!$player || !$player->registration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inscription non trouvée'
                ], 404);
            }

            // Stocker le nouveau document
            $file = $request->file('document');
            $path = $file->store('registrations/' . $player->id, 'public');

            // Ajouter le document à l'inscription
            $documents = $player->registration->documents ?? [];
            $documents[$request->document_key] = $path;

            $player->registration->documents = $documents;
            $player->registration->save();

            // Déterminer le type de document
            $extension = $file->getClientOriginalExtension();
            $type = $this->getDocumentType($extension);

            // Journaliser l'action
            Log::info('Document ajouté', [
                'player_id' => $player->id,
                'document_key' => $request->document_key,
                'path' => $path
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document ajouté avec succès',
                'path' => $path,
                'url' => asset('storage/' . $path),
                'extension' => $extension,
                'type' => $type
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'ajout du document', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnecté avec succès'
        ]);
    }

    /**
     * Vérifier si un étudiant inscrit peut se connecter en tant que joueur
     */
    public function checkRegistration(Request $request)
    {
        try {
            // Valider les données
            $validator = Validator::make($request->all(), [
                'firstname' => 'required|string',
                'lastname' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation: ' . implode(', ', $validator->errors()->all()),
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier d'abord si le joueur existe dans la table player
            $player = Player::where(function($query) use ($request) {
                // Recherche exacte
                $query->where('firstname', $request->firstname)
                      ->where('lastname', $request->lastname);
            })
            ->orWhere(function($query) use ($request) {
                // Recherche insensible à la casse
                $query->whereRaw('LOWER(firstname) = ?', [strtolower($request->firstname)])
                      ->whereRaw('LOWER(lastname) = ?', [strtolower($request->lastname)]);
            })
            ->first();

            if (!$player) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas enregistré comme joueur dans notre académie.',
                    'exists' => false
                ], 404);
            }

            // Maintenant, vérifier si ce joueur a une inscription avec un email
            $registration = \App\Models\Registration::where('player_id', $player->id)
                ->whereNotNull('player_email')
                ->first();

            if (!$registration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune inscription avec email trouvée pour ce joueur.',
                    'exists' => false
                ], 404);
            }

            // Vérifier si un utilisateur existe déjà avec cet email
            $existingUser = User::where('email', $registration->player_email)->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un compte avec cette adresse email existe déjà. Veuillez vous connecter.',
                    'exists' => true
                ], 409);
            }

            // Retourner les informations pour la création du compte
            return response()->json([
                'success' => true,
                'message' => 'Inscription trouvée. Vous pouvez créer un compte.',
                'exists' => true,
                'player' => [
                    'id' => $player->id,
                    'firstname' => $player->firstname,
                    'lastname' => $player->lastname
                ],
                'registration' => [
                    'id' => $registration->id,
                    'status' => $registration->status,
                    'payment_status' => $registration->payment_status,
                    'email' => $registration->player_email
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification de l\'inscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification: ' . $e->getMessage(),
                'exists' => false
            ], 500);
        }
    }
}
