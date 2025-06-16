<?php

namespace App\Http\Controllers;

use App\Models\MatchModel;
use Illuminate\Http\Request;


class MatchController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('category_id')) {
            return MatchModel::where('category_id', $request->category_id)
                      ->with('category')
                      ->get();
        }

        return MatchModel::with('category')->get();
    }

    public function store(Request $request)
    {
        return MatchModel::create($request->all());
    }

    public function show(MatchModel $match)
    {
        return $match;
    }

    public function update(Request $request, MatchModel $match)
    {
        $match->update($request->all());
        return $match;
    }

    public function destroy(MatchModel $match)
    {
        $match->delete();
        return response()->noContent();
    }

    /**
     * Récupérer les matchs par catégorie
     * 
     * @param int $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMatchesByCategory($categoryId)
    {
        \Log::info('getMatchesByCategory appelé avec categoryId: ' . $categoryId);
        
        $matches = MatchModel::where('category_id', $categoryId)
                    ->orderBy('date', 'asc')
                    ->with('category')
                    ->get();
        
        \Log::info('Nombre de matchs trouvés: ' . count($matches));
                    
        return response()->json($matches);
    }

    /**
     * Permettre au coach d'ajouter un match pour sa catégorie
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeByCoach(Request $request)
    {
        \Log::info('storeByCoach appelé avec les données: ' . json_encode($request->all()));
        
        // Valider les données
        $validatedData = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'date' => 'required|date',
            'time' => 'nullable|string',
            'opponent' => 'required|string',
            'location' => 'required|string',
            'result' => 'nullable|string'
        ]);
        
        // Vérifier que le coach est bien associé à cette catégorie
        $user = $request->user();
        if (!$user || $user->role !== 'coach') {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être connecté en tant que coach pour ajouter un match'
            ], 403);
        }
        
        $coach = \App\Models\Coach::where('email', $user->email)->first();
        if (!$coach || $coach->category_id != $validatedData['category_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez ajouter des matchs que pour votre propre catégorie'
            ], 403);
        }
        
        // Créer le match
        $match = MatchModel::create($validatedData);
        
        return response()->json([
            'success' => true,
            'message' => 'Match ajouté avec succès',
            'match' => $match
        ], 201);
    }

    /**
     * Permettre au coach de modifier un match pour sa catégorie
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $matchId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateByCoach(Request $request, $matchId)
    {
        \Log::info('updateByCoach appelé avec les données: ' . json_encode($request->all()));
        
        // Trouver le match
        $match = MatchModel::find($matchId);
        
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match non trouvé'
            ], 404);
        }
        
        // Valider les données
        $validatedData = $request->validate([
            'date' => 'required|date',
            'time' => 'nullable|string',
            'opponent' => 'required|string',
            'location' => 'required|string',
            'result' => 'nullable|string'
        ]);
        
        // Vérifier que le coach est bien associé à cette catégorie
        $user = $request->user();
        if (!$user || $user->role !== 'coach') {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être connecté en tant que coach pour modifier un match'
            ], 403);
        }
        
        $coach = \App\Models\Coach::where('email', $user->email)->first();
        if (!$coach || $coach->category_id != $match->category_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez modifier que les matchs de votre propre catégorie'
            ], 403);
        }
        
        // Mettre à jour le match
        $match->update($validatedData);
        
        return response()->json([
            'success' => true,
            'message' => 'Match modifié avec succès',
            'match' => $match
        ]);
    }
    
    /**
     * Permettre au coach de supprimer un match pour sa catégorie
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $matchId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyByCoach(Request $request, $matchId)
    {
        \Log::info('destroyByCoach appelé pour matchId: ' . $matchId);
        
        // Trouver le match
        $match = MatchModel::find($matchId);
        
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match non trouvé'
            ], 404);
        }
        
        // Vérifier que le coach est bien associé à cette catégorie
        $user = $request->user();
        if (!$user || $user->role !== 'coach') {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être connecté en tant que coach pour supprimer un match'
            ], 403);
        }
        
        $coach = \App\Models\Coach::where('email', $user->email)->first();
        if (!$coach || $coach->category_id != $match->category_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez supprimer que les matchs de votre propre catégorie'
            ], 403);
        }
        
        // Supprimer le match
        $match->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Match supprimé avec succès'
        ]);
    }
}
