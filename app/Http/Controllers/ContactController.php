<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        // Si le paramètre recent est présent, retourner uniquement les contacts non lus
        if ($request->has('recent')) {
            return Contact::where('read', false)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        }
        
        // Sinon, retourner tous les contacts avec pagination
        return Contact::orderBy('created_at', 'desc')->paginate(15);
    }

    public function store(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'childAge' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $contact = Contact::create($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Message de contact créé avec succès',
            'data' => $contact
        ], 201);
    }

    public function show(Contact $contact)
    {
        // Marquer comme lu si ce n'est pas déjà fait
        if (!$contact->read) {
            $contact->read = true;
            $contact->save();
        }
        
        return $contact;
    }
    
    public function update(Request $request, Contact $contact)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'message' => 'sometimes|required|string',
            'phone' => 'nullable|string|max:20',
            'childAge' => 'nullable|string|max:10',
            'read' => 'sometimes|boolean',
            'response' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Si une réponse est fournie, enregistrer la date de réponse
        if ($request->has('response') && !empty($request->response) && ($contact->response !== $request->response)) {
            $request->merge(['responded_at' => Carbon::now()]);
            
            // Envoyer l'email de réponse
            try {
                $emailSent = $this->sendResponseEmail($contact, $request->response);
                if (!$emailSent) {
                    \Log::warning("L'email n'a pas pu être envoyé à {$contact->email}, mais la réponse sera enregistrée");
                }
            } catch (\Exception $e) {
                \Log::error("Erreur lors de l'envoi de l'email, mais la réponse sera enregistrée", [
                    'error' => $e->getMessage()
                ]);
                // Ne pas bloquer la mise à jour même si l'email échoue
            }
        }

        $contact->update($request->all());
        
        return response()->json([
            'success' => true,
            'message' => 'Message de contact mis à jour avec succès',
            'data' => $contact
        ]);
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Message de contact supprimé avec succès'
        ]);
    }
    
    /**
     * Répondre à un contact
     */
    public function respond(Request $request, Contact $contact)
    {
        \Log::info('Méthode respond appelée', [
            'contact_id' => $contact->id,
            'request_data' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'response' => 'required|string',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation échouée', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            \Log::info('Tentative d\'envoi d\'email', [
                'email' => $contact->email,
                'response' => $request->response
            ]);
            
            // Mettre à jour le contact d'abord
            $contact->response = $request->response;
            $contact->responded_at = Carbon::now();
            $contact->read = true;
            $contact->save();
            
            // Envoyer l'email de réponse
            $emailSent = $this->sendResponseEmail($contact, $request->response);
            
            \Log::info('Réponse enregistrée avec succès', [
                'contact_id' => $contact->id,
                'email_sent' => $emailSent
            ]);
            
            return response()->json([
                'success' => true,
                'message' => $emailSent ? 'Réponse envoyée avec succès' : 'Réponse enregistrée mais l\'envoi d\'email a échoué',
                'email_sent' => $emailSent,
                'data' => $contact
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'envoi de la réponse', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la réponse',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Envoyer un email de réponse
     */
    private function sendResponseEmail(Contact $contact, string $response)
    {
        try {
            // Utiliser la classe Mail de Laravel pour envoyer l'email
            // Créer l'instance de mailable
            $email = new \App\Mail\ContactResponse($contact, $response);
            
            // Envoyer l'email
            \Illuminate\Support\Facades\Mail::send($email);
            
            // Logger l'envoi réussi
            \Log::info("Email envoyé avec succès à {$contact->email}", [
                'contact_id' => $contact->id,
                'email_class' => get_class($email)
            ]);
            
            return true;
        } catch (\Exception $e) {
            // Logger l'erreur
            \Log::error("Erreur lors de l'envoi de l'email à {$contact->email}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Ne pas relancer l'exception, retourner false à la place
            // Cela permettra à l'API de continuer à fonctionner même si l'email échoue
            return false;
        }
    }
}
