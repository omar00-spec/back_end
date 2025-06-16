<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RegistrationController extends Controller
{
    public function index()
    {
        return Registration::with('category')->get();
    }

    // Méthode pour récupérer les inscriptions avec les détails complets
    public function getDetailedRegistrations()
    {
        $registrations = Registration::with('category')->get();
        
        $detailedRegistrations = $registrations->map(function ($registration) {
            $category = $registration->category;
            
            return [
                'id' => $registration->id,
                'player_firstname' => $registration->player_firstname,
                'player_lastname' => $registration->player_lastname,
                'birth_date' => $registration->birth_date,
                'category' => $category ? $category->name : null,
                'category_id' => $registration->category_id,
                'address' => $registration->address,
                'city' => $registration->city,
                'player_phone' => $registration->player_phone,
                'player_email' => $registration->player_email,
                'parent_name' => $registration->parent_name,
                'parent_phone' => $registration->parent_phone,
                'parent_email' => $registration->parent_email,
                'payment_method' => $registration->payment_method,
                'status' => $registration->status,
                'payment_status' => $registration->payment_status,
                'created_at' => $registration->created_at,
                'updated_at' => $registration->updated_at
            ];
        });
        
        return response()->json($detailedRegistrations);
    }

    public function store(Request $request)
    {
        // Déboguer les données reçues
        \Log::info('Données reçues du formulaire:', $request->all());
        
        // Valider les données
        $validator = Validator::make($request->all(), [
            'playerName' => 'required|string|max:255',
            'playerFirstName' => 'required|string|max:255',
            'birthDate' => 'required|date',
            'category' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'playerPhone' => 'nullable|string',
            'playerEmail' => 'nullable|email',
            'parentName' => 'nullable|string',
            'parentPhone' => 'nullable|string',
            'parentEmail' => 'nullable|email',
            'paymentMethod' => 'required|string|in:Espèces,Chèque,Virement bancaire',
            'documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Traiter les fichiers
        $documents = [];

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $type => $file) {
                // Utiliser un identifiant unique pour le dossier de stockage
                $uniqueId = uniqid();
                $path = $file->store('registrations/' . $uniqueId, 'public');
                $documents[$type] = $path;
            }
        }

        // Préparer les données pour l'inscription
        $registrationData = [
            'player_lastname' => $request->playerName,
            'player_firstname' => $request->playerFirstName,
            'birth_date' => $request->birthDate,
            'category_id' => $request->category,
            'parent_name' => $request->parentName,
            'parent_email' => $request->parentEmail,
            'parent_phone' => $request->parentPhone,
            'address' => $request->address,
            'city' => $request->city,
            'player_phone' => $request->playerPhone,
            'player_email' => $request->playerEmail,
            'documents' => $documents,
            'payment_method' => $request->paymentMethod,
            'status' => 'pending',
            'payment_status' => 'pending',
        ];
        
        // Déboguer les données avant création
        \Log::info('Données d\'inscription avant création:', $registrationData);
        
        // Créer l'inscription
        $registration = Registration::create($registrationData);

        // Préparer la réponse en fonction du mode de paiement
        $response = [
            'success' => true,
            'registration' => $registration,
        ];

        // Ajouter les informations spécifiques selon le mode de paiement
        switch ($request->paymentMethod) {
            case 'Virement bancaire':
                $response['redirect'] = true;
                $response['redirect_url'] = '/payment/bank-transfer';
                $response['registration_id'] = $registration->id;
                // Les informations de paiement seront générées par Stripe du côté client
                break;

            case 'Chèque':
                $response['redirect'] = true;
                $response['redirect_url'] = '/payment/check';
                $response['payment_info'] = [
                    'recipient' => 'ACOS Football Academy',
                    'address' => '123 Avenue Mohammed V, Rabat, Maroc',
                    'reference' => 'REG-' . $registration->id,
                ];
                break;

            case 'Espèces':
                $response['redirect'] = false;
                break;
        }

        return response()->json($response, 201);
    }

    public function show(Registration $registration)
    {
        return $registration->load('category');
    }

    public function update(Request $request, Registration $registration)
    {
        $registration->update($request->all());
        return $registration;
    }

    public function destroy(Registration $registration)
    {
        // Supprimer les fichiers associés
        if (!empty($registration->documents)) {
            foreach ($registration->documents as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $registration->delete();
        return response()->noContent();
    }
    
    /**
     * Récupérer les inscriptions en attente
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingRegistrations(Request $request)
    {
        $status = $request->query('status', 'pending');
        
        $registrations = Registration::where('status', $status)
            ->with('category')
            ->get();
            
        return response()->json($registrations);
    }
    
    /**
     * Accepter une inscription et créer un joueur
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptRegistration(Request $request, $id)
    {
        try {
            // Trouver l'inscription
            $registration = Registration::findOrFail($id);
            
            // Vérifier si l'inscription est déjà acceptée
            if ($registration->status === 'accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette inscription a déjà été acceptée.'
                ], 400);
            }
            
            // Créer un joueur à partir des données d'inscription
            $player = \App\Models\Player::create([
                'firstname' => $registration->player_firstname,
                'lastname' => $registration->player_lastname,
                'birth_date' => $registration->birth_date,
                'category_id' => $registration->category_id,
                'team' => $request->team ?? null,
                'yellow_cards' => 0
            ]);
            
            // Mettre à jour l'inscription
            $registration->player_id = $player->id;
            $registration->status = 'accepted';
            $registration->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Inscription acceptée avec succès.',
                'player' => $player,
                'registration' => $registration
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'acceptation de l\'inscription: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'acceptation de l\'inscription: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Rejeter une inscription
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectRegistration($id)
    {
        try {
            // Trouver l'inscription
            $registration = Registration::findOrFail($id);
            
            // Mettre à jour le statut
            $registration->status = 'rejected';
            $registration->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Inscription rejetée avec succès.',
                'registration' => $registration
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors du rejet de l\'inscription: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du rejet de l\'inscription: ' . $e->getMessage()
            ], 500);
        }
    }
}
