<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Envoyer un email avec un lien de ru00e9initialisation du mot de passe
     */
    public function sendResetLinkEmail(Request $request)
    {
        // Valider l'email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Aucun compte n\'est associu00e9 u00e0 cette adresse email.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Adresse email invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        // Gu00e9nu00e9rer un token unique
        $token = Str::random(60);

        // Enregistrer le token dans la base de donnu00e9es
        DB::table('password_resets')->where('email', $request->email)->delete();

        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now()
        ]);

        // Construire l'URL de réinitialisation
        $resetUrl = env('FRONTEND_URL', 'http://localhost:3001') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        try {
            // Envoyer l'email avec le lien de réinitialisation
            Mail::send('emails.reset_password', ['resetUrl' => $resetUrl], function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('Réinitialisation de votre mot de passe');
            });

            // En production, ne pas inclure l'URL dans la réponse pour des raisons de sécurité
            if (env('APP_ENV') === 'production') {
                return response()->json([
                    'success' => true,
                    'message' => 'Un lien de réinitialisation a été envoyé à votre adresse email.'
                ]);
            } else {
                // En développement, inclure l'URL pour faciliter les tests
                return response()->json([
                    'success' => true,
                    'message' => 'Un lien de réinitialisation a été envoyé à votre adresse email. En mode développement, l\'URL est également fournie ci-dessous.',
                    'reset_url' => $resetUrl
                ]);
            }
        } catch (\Exception $e) {
            // En cas d'erreur d'envoi d'email, journaliser l'erreur
            Log::error("Erreur d'envoi d'email: " . $e->getMessage());

            // En développement, inclure l'URL même en cas d'erreur
            if (env('APP_ENV') !== 'production') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi de l\'email. En mode développement, l\'URL est fournie ci-dessous.',
                    'error' => $e->getMessage(),
                    'reset_url' => $resetUrl
                ], 500);
            }

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi de l\'email de réinitialisation.'
            ], 500);
        }
    }

    /**
     * Générer un lien de réinitialisation sans envoi d'email (pour le développement uniquement)
     */
    public function generateResetLink(Request $request)
    {
        // Valider l'email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Adresse email invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        // Générer un token unique
        $token = Str::random(60);

        // Enregistrer le token dans la base de données
        DB::table('password_resets')->where('email', $request->email)->delete();

        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now()
        ]);

        // Construire l'URL de réinitialisation
        $resetUrl = env('FRONTEND_URL', 'http://localhost:3001') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        return response()->json([
            'success' => true,
            'message' => 'Lien de réinitialisation généré avec succès (sans envoi d\'email).',
            'reset_url' => $resetUrl
        ]);
    }

    /**
     * Méthode pour gérer la réinitialisation de mot de passe via l'interface web
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Vérifier si le token est valide
        $resetRecord = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return redirect()->back()
                ->withErrors(['token' => 'Ce lien de réinitialisation est invalide.']);
        }

        // Vérifier si le token est expiré (1 heure)
        $createdAt = Carbon::parse($resetRecord->created_at);
        if (Carbon::now()->diffInMinutes($createdAt) > 60) {
            DB::table('password_resets')->where('email', $request->email)->delete();

            return redirect()->back()
                ->withErrors(['token' => 'Ce lien de réinitialisation a expiré.']);
        }

        // Vérifier si le token correspond
        if (!Hash::check($request->token, $resetRecord->token)) {
            return redirect()->back()
                ->withErrors(['token' => 'Ce lien de réinitialisation est invalide.']);
        }

        // Mettre à jour le mot de passe
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Supprimer le token de réinitialisation
        DB::table('password_resets')->where('email', $request->email)->delete();

        return redirect('/login')->with('status', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.');
    }

    /**
     * Réinitialiser le mot de passe (API)
     */
    public function reset(Request $request)
    {
        // Valider les données
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Donnu00e9es invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vu00e9rifier si le token est valide
        $resetRecord = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Ce lien de ru00e9initialisation est invalide.'
            ], 400);
        }

        // Vu00e9rifier si le token est expiru00e9 (1 heure)
        $createdAt = Carbon::parse($resetRecord->created_at);
        if (Carbon::now()->diffInMinutes($createdAt) > 60) {
            DB::table('password_resets')->where('email', $request->email)->delete();

            return response()->json([
                'success' => false,
                'message' => 'Ce lien de ru00e9initialisation a expiru00e9.'
            ], 400);
        }

        // Vu00e9rifier si le token correspond
        if (!Hash::check($request->token, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce lien de ru00e9initialisation est invalide.'
            ], 400);
        }

        // Mettre u00e0 jour le mot de passe
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Supprimer le token de ru00e9initialisation
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Votre mot de passe a u00e9tu00e9 ru00e9initialisu00e9 avec succu00e8s.'
        ]);
    }
}
