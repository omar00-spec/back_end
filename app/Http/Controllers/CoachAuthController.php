<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Coach;

class CoachAuthController extends Controller
{
    /**
     * Enregistrer un nouveau coach
     */
    public function register(Request $request)
    {
        try {
            // Journaliser la requête reçue
            Log::info('Requête d\'enregistrement coach reçue', [
                'data' => $request->all()
            ]);
            
            // Valider les données
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|string|email|unique:users,email',
                'phone' => 'required|string',
                'diploma' => 'nullable|string',
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

            // Vérifier si le coach existe déjà dans la table coach
            $existingCoach = Coach::where('email', $request->email)
                ->orWhere('name', 'like', '%' . $request->name . '%')
                ->first();

            if (!$existingCoach) {
                // Créer un nouveau coach dans la table coach
                $coach = Coach::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'diploma' => $request->diploma,
                    'category_id' => 1, // Valeur par défaut pour la catégorie
                ]);
            } else {
                // Mettre à jour le coach existant
                $existingCoach->email = $request->email;
                $existingCoach->phone = $request->phone;
                if ($request->diploma) {
                    $existingCoach->diploma = $request->diploma;
                }
                $existingCoach->save();
                
                $coach = $existingCoach;
            }

            // Générer un mot de passe aléatoire
            $password = Str::random(10);
            
            // Créer un utilisateur pour ce coach
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($password),
                'role' => 'coach',
            ]);

            Log::info('Coach enregistré avec succès', [
                'coach_id' => $coach->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Coach enregistré avec succès',
                'password' => $password,
                'coach' => $coach,
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement du coach', [
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
     * Connexion d'un coach
     */
    public function login(Request $request)
    {
        // Journaliser la tentative de connexion
        Log::info('Tentative de connexion coach', [
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

        // Vérifier si l'utilisateur existe et si c'est un coach
        if (!$user || $user->role !== 'coach' || !Hash::check($fields['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides ou vous n\'êtes pas un coach'
            ], 401);
        }

        // Vérifier si le coach existe dans la table coach
        $coach = Coach::where('email', $user->email)->first();
        if (!$coach) {
            Log::warning('Profil coach non trouvé', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            // Vérifier s'il existe un coach avec le même nom
            $coachByName = Coach::where('name', 'like', '%' . $user->name . '%')->first();
                
            if ($coachByName) {
                Log::info('Coach trouvé par nom', [
                    'coach_id' => $coachByName->id,
                    'name' => $coachByName->name
                ]);
                
                $coach = $coachByName;
                
                // Mettre à jour l'email du coach s'il n'en a pas
                if (!$coach->email) {
                    $coach->email = $user->email;
                    $coach->save();
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre profil de coach n\'existe pas dans notre système'
                ], 401);
            }
        }

        // Récupérer la catégorie du coach
        $category = $coach->category;

        // Créer un token
        $token = $user->createToken('coachtoken')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'coach' => $coach,
            'category' => $category,
            'token' => $token
        ], 200);
    }

    /**
     * Profil du coach (sécurisé)
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        // Vérifier si c'est un coach
        if ($user->role !== 'coach') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        // Récupérer les informations du coach
        $coach = Coach::with(['category'])->where('email', $user->email)->first();
        
        if (!$coach) {
            return response()->json([
                'success' => false,
                'message' => 'Profil de coach non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user,
            'coach' => $coach,
            'category' => $coach->category
        ], 200);
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