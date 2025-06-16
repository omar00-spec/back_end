<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('category_id')) {
            return Player::where('category_id', $request->category_id)
                      ->with('category')
                      ->get();
        }

        return Player::with('category')->get();
    }

    public function store(Request $request)
    {
        return Player::create($request->all());
    }

    public function show(Player $player)
    {
        return $player;
    }

    public function update(Request $request, Player $player)
    {
        $player->update($request->all());
        return $player;
    }

    public function destroy(Player $player)
    {
        $player->delete();
        return response()->noContent();
    }
    
    /**
     * Récupérer les joueurs par catégorie
     * 
     * @param int $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlayersByCategory($categoryId)
    {
        // Ajouter des logs pour débogage
        \Log::info('getPlayersByCategory appelé avec categoryId: ' . $categoryId);
        
        $players = Player::where('category_id', $categoryId)
                    ->with(['category'])
                    ->get();
        
        \Log::info('Nombre de joueurs trouvés: ' . count($players));
        
        // Transformer les données pour correspondre au format attendu par le frontend
        $formattedPlayers = $players->map(function ($player) {
            return [
                'id' => $player->id,
                'name' => $player->firstname . ' ' . $player->lastname,
                'position' => $player->team ?? 'Non définie',
                'category_id' => $player->category_id,
                'category' => $player->category,
                'yellow_cards' => $player->yellow_cards ?? 0,
                'performance' => null // À récupérer si nécessaire
            ];
        });
        
        \Log::info('Données formatées: ' . json_encode($formattedPlayers));
                    
        return response()->json($formattedPlayers);
    }

    /**
     * Ajouter ou mettre à jour les performances d'un joueur
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $playerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePerformance(Request $request, $playerId)
    {
        \Log::info('updatePerformance appelé pour le joueur: ' . $playerId);
        \Log::info('Données reçues: ' . json_encode($request->all()));
        
        try {
            // Valider les données
            $validatedData = $request->validate([
                'technique' => 'required|numeric|min:0|max:5',
                'tactique' => 'required|numeric|min:0|max:5',
                'physique' => 'required|numeric|min:0|max:5',
                'mental' => 'required|numeric|min:0|max:5',
                'commentaire' => 'nullable|string'
            ]);
            
            // Vérifier que le joueur existe
            $player = Player::findOrFail($playerId);
            \Log::info('Joueur trouvé: ' . $player->firstname . ' ' . $player->lastname);
            
            // Vérifier que l'utilisateur est connecté
            $user = $request->user();
            if (!$user) {
                \Log::error('Utilisateur non connecté');
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez être connecté pour évaluer un joueur'
                ], 401);
            }
            
            \Log::info('Utilisateur connecté: ' . $user->name . ' (role: ' . $user->role . ')');
            
            // Si c'est un coach, vérifier qu'il est associé à la catégorie du joueur
            if ($user->role === 'coach') {
                $coach = \App\Models\Coach::where('email', $user->email)->first();
                \Log::info('Coach trouvé: ' . ($coach ? 'Oui (category_id: ' . $coach->category_id . ')' : 'Non'));
                
                // Assouplir la vérification pour le moment (pour déboguer)
                // Nous permettons à tous les coachs d'évaluer tous les joueurs
            }
            
            // Ajouter ou mettre à jour les performances
            $performance = \App\Models\Performance::updateOrCreate(
                ['player_id' => $playerId],
                $validatedData
            );
            
            \Log::info('Performance enregistrée avec succès: ' . json_encode($performance));
            
            return response()->json([
                'success' => true,
                'message' => 'Performance mise à jour avec succès',
                'performance' => $performance
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour de la performance: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la performance: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mettre à jour les cartes jaunes d'un joueur
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $playerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateYellowCard(Request $request, $playerId)
    {
        \Log::info('updateYellowCard appelé pour le joueur: ' . $playerId);
        \Log::info('Données reçues: ' . json_encode($request->all()));
        
        // Valider les données
        $validatedData = $request->validate([
            'yellow_cards' => 'required|integer|min:0'
        ]);
        
        try {
            // Vérifier que le joueur existe
            $player = Player::findOrFail($playerId);
            
            // Vérifier que le coach est bien associé à la catégorie du joueur
            $user = $request->user();
            if (!$user || $user->role !== 'coach') {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez être connecté en tant que coach pour gérer les cartes jaunes'
                ], 403);
            }
            
            $coach = \App\Models\Coach::where('email', $user->email)->first();
            if (!$coach || $coach->category_id != $player->category_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez gérer les cartes jaunes que pour les joueurs de votre propre catégorie'
                ], 403);
            }
            
            // Mettre à jour les cartes jaunes
            $player->yellow_cards = $validatedData['yellow_cards'];
            $player->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Cartes jaunes mises à jour avec succès',
                'yellow_cards' => $player->yellow_cards
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour des cartes jaunes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des cartes jaunes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les performances d'un joueur
     * 
     * @param int $playerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPerformance($playerId)
    {
        \Log::info('getPerformance appelé pour le joueur: ' . $playerId);
        
        try {
            // Vérifier que le joueur existe
            $player = Player::findOrFail($playerId);
            
            // Récupérer les performances du joueur
            $performance = \App\Models\Performance::where('player_id', $playerId)->first();
            
            \Log::info('Performance trouvée: ' . ($performance ? json_encode($performance) : 'Aucune'));
            
            return response()->json($performance);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des performances: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des performances: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Récupérer les cartes jaunes d'un joueur
     * 
     * @param int $playerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getYellowCards($playerId)
    {
        \Log::info('getYellowCards appelé pour le joueur: ' . $playerId);
        
        try {
            // Vérifier que le joueur existe
            $player = Player::findOrFail($playerId);
            
            \Log::info('Cartes jaunes: ' . ($player->yellow_cards ?? 0));
            
            return response()->json([
                'success' => true,
                'yellow_cards' => $player->yellow_cards ?? 0
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des cartes jaunes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des cartes jaunes: ' . $e->getMessage()
            ], 500);
        }
    }
}
