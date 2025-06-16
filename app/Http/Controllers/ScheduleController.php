<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('category_id')) {
            return Schedule::where('category_id', $request->category_id)
                      ->with('category')
                      ->get();
        }

        return Schedule::with('category')->get();
    }

    public function store(Request $request)
    {
        return Schedule::create($request->all());
    }

    public function show(Schedule $schedule)
    {
        return $schedule;
    }

    public function update(Request $request, Schedule $schedule)
    {
        $schedule->update($request->all());
        return $schedule;
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return response()->noContent();
    }

    /**
     * Récupérer les entraînements par catégorie
     * 
     * @param int $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSchedulesByCategory($categoryId)
    {
        \Log::info('getSchedulesByCategory appelé avec categoryId: ' . $categoryId);
        
        $schedules = Schedule::where('category_id', $categoryId)
                    ->orderBy('day', 'asc')
                    ->orderBy('start_time', 'asc')
                    ->with('category')
                    ->get();
        
        \Log::info('Nombre d\'entraînements trouvés: ' . count($schedules));
                    
        return response()->json($schedules);
    }
    
    /**
     * Permettre au coach d'ajouter un entraînement pour sa catégorie
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
            'day' => 'required|string',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'activity' => 'required|string'
        ]);
        
        // Vérifier que le coach est bien associé à cette catégorie
        $user = $request->user();
        if (!$user || $user->role !== 'coach') {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être connecté en tant que coach pour ajouter un entraînement'
            ], 403);
        }
        
        $coach = \App\Models\Coach::where('email', $user->email)->first();
        if (!$coach || $coach->category_id != $validatedData['category_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez ajouter des entraînements que pour votre propre catégorie'
            ], 403);
        }
        
        // Créer l'entraînement
        $schedule = Schedule::create($validatedData);
        
        return response()->json([
            'success' => true,
            'message' => 'Entraînement ajouté avec succès',
            'schedule' => $schedule
        ], 201);
    }

    /**
     * Permettre au coach de modifier un entraînement pour sa catégorie
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $scheduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateByCoach(Request $request, $scheduleId)
    {
        \Log::info('updateByCoach appelé avec les données: ' . json_encode($request->all()));
        
        // Trouver l'entraînement
        $schedule = Schedule::find($scheduleId);
        
        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Entraînement non trouvé'
            ], 404);
        }
        
        // Valider les données
        $validatedData = $request->validate([
            'day' => 'required|string',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'activity' => 'required|string'
        ]);
        
        // Vérifier que le coach est bien associé à cette catégorie
        $user = $request->user();
        if (!$user || $user->role !== 'coach') {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être connecté en tant que coach pour modifier un entraînement'
            ], 403);
        }
        
        $coach = \App\Models\Coach::where('email', $user->email)->first();
        if (!$coach || $coach->category_id != $schedule->category_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez modifier que les entraînements de votre propre catégorie'
            ], 403);
        }
        
        // Mettre à jour l'entraînement
        $schedule->update($validatedData);
        
        return response()->json([
            'success' => true,
            'message' => 'Entraînement modifié avec succès',
            'schedule' => $schedule
        ]);
    }
    
    /**
     * Permettre au coach de supprimer un entraînement pour sa catégorie
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $scheduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyByCoach(Request $request, $scheduleId)
    {
        \Log::info('destroyByCoach appelé pour scheduleId: ' . $scheduleId);
        
        // Trouver l'entraînement
        $schedule = Schedule::find($scheduleId);
        
        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Entraînement non trouvé'
            ], 404);
        }
        
        // Vérifier que le coach est bien associé à cette catégorie
        $user = $request->user();
        if (!$user || $user->role !== 'coach') {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être connecté en tant que coach pour supprimer un entraînement'
            ], 403);
        }
        
        $coach = \App\Models\Coach::where('email', $user->email)->first();
        if (!$coach || $coach->category_id != $schedule->category_id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez supprimer que les entraînements de votre propre catégorie'
            ], 403);
        }
        
        // Supprimer l'entraînement
        $schedule->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Entraînement supprimé avec succès'
        ]);
    }
}
