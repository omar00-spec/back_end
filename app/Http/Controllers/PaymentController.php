<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{   
    public function __construct()
    {
        // Initialiser Stripe dans le constructeur
        $stripeKey = env('STRIPE_SECRET_KEY');
        if ($stripeKey) {
            \Stripe\Stripe::setApiKey($stripeKey);
        } else {
            Log::error('Clé API Stripe non configurée');
        }
    }

    /**
     * Créer une session de paiement pour un virement bancaire via Stripe
     */
    public function createBankTransferSession(Request $request)
    {
        try {
            // Vérifier si l'ID d'inscription est fourni
            if (!$request->has('registration_id')) {
                return $this->errorResponse('ID d\'inscription manquant', 400);
            }

            // Vérifier que la clé Stripe est configurée
            if (!env('STRIPE_SECRET_KEY')) {
                return $this->errorResponse('Configuration de paiement incorrecte. Veuillez contacter l\'administrateur.', 500);
            }
            
            // Journalisation pour débogage
            Log::info('Demande de création de session de paiement', [
                'registration_id' => $request->registration_id
            ]);

            // Récupérer l'inscription
            $registration = Registration::with('player')->find($request->registration_id);
            
            if (!$registration) {
                Log::error('Inscription non trouvée', ['id' => $request->registration_id]);
                return $this->errorResponse('Inscription non trouvée', 404);
            }

            // Définir le prix en fonction de la catégorie ou un prix fixe
            $price = 500; // 500 MAD par exemple, à ajuster selon vos besoins

            // Informations du joueur pour la description
            $playerName = $this->getPlayerName($registration);

            // Créer une session de paiement Stripe
            $session = Session::create([
                'payment_method_types' => ['card'], // Simplification des méthodes de paiement
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur', // EUR est supporté par Stripe
                        'product_data' => [
                            'name' => 'Inscription - ACOS Football Academy',
                            'description' => 'Inscription pour ' . $playerName,
                        ],
                        'unit_amount' => $price * 100, // Stripe utilise les centimes
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => env('APP_FRONTEND_URL', 'http://localhost:3000') . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('APP_FRONTEND_URL', 'http://localhost:3000') . '/payment/cancel',
                'client_reference_id' => $registration->id,
                'metadata' => [
                    'registration_id' => $registration->id,
                    'player_name' => $playerName,
                ],
            ]);

            // Journalisation de la session créée
            Log::info('Session de paiement créée', [
                'session_id' => $session->id,
                'registration_id' => $registration->id
            ]);

            // Mettre à jour le statut de l'inscription
            $registration->payment_status = 'processing';
            $registration->save();

            return response()->json([
                'success' => true,
                'session_id' => $session->id,
                'checkout_url' => $session->url,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return $this->errorResponse('Erreur lors de la création de la session de paiement: ' . $e->getMessage(), 500);
        } catch (\Exception $e) {
            Log::error('Payment Error: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Une erreur est survenue: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Webhook pour traiter les événements Stripe
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );

            Log::info('Webhook reçu: ' . $event->type);

            // Traiter différents types d'événements
            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object;
                    $this->handleSuccessfulPayment($session);
                    break;

                case 'checkout.session.async_payment_succeeded':
                    $session = $event->data->object;
                    $this->handleSuccessfulPayment($session);
                    break;

                case 'checkout.session.async_payment_failed':
                    $session = $event->data->object;
                    // Vérifier si payment_intent existe avant de l'utiliser
                    if (isset($session->payment_intent)) {
                        // Si c'est une chaîne, récupérer l'objet payment_intent
                        if (is_string($session->payment_intent)) {
                            try {
                                $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
                                $this->handleFailedPayment($paymentIntent);
                            } catch (\Exception $e) {
                                Log::error('Erreur lors de la récupération du payment_intent: ' . $e->getMessage());
                            }
                        } else {
                            $this->handleFailedPayment($session->payment_intent);
                        }
                    } else {
                        Log::warning('Session sans payment_intent dans checkout.session.async_payment_failed');
                    }
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    $this->handleFailedPayment($paymentIntent);
                    break;

                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    // Trouver l'inscription associée
                    $registration = Registration::where('payment_reference', $paymentIntent->id)->first();
                    if ($registration) {
                        $registration->payment_status = 'completed';
                        $registration->save();
                        Log::info('Paiement marqué comme complété pour l\'inscription #' . $registration->id);
                    }
                    break;
            }

            return response()->json(['status' => 'success']);
        } catch (\UnexpectedValueException $e) {
            // Signature invalide
            Log::error('Signature Stripe invalide: ' . $e->getMessage());
            return $this->errorResponse('Signature invalide', 400);
        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement du webhook: ' . $e->getMessage());
            return $this->errorResponse('Erreur serveur: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Récupère le nom du joueur depuis l'objet d'inscription
     */
    private function getPlayerName($registration)
    {
        if ($registration->player) {
            return $registration->player->firstname . ' ' . $registration->player->lastname;
        } elseif ($registration->player_firstname && $registration->player_lastname) {
            return $registration->player_firstname . ' ' . $registration->player_lastname;
        }
        return "Joueur";
    }
    
    /**
     * Génère une réponse d'erreur standardisée
     */
    private function errorResponse($message, $statusCode)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }

    /**
     * Traiter un paiement réussi
     */
    private function handleSuccessfulPayment($session)
    {
        $registrationId = $session->client_reference_id;

        $registration = Registration::find($registrationId);
        if ($registration) {
            $registration->payment_status = 'completed';
            $registration->payment_method = 'Virement bancaire (Stripe)';
            $registration->payment_reference = $session->payment_intent;
            $registration->save();
            Log::info('Paiement réussi pour l\'inscription #' . $registrationId);
            // Vous pourriez également envoyer un email de confirmation ici
        } else {
            Log::warning('Aucune inscription trouvée pour client_reference_id: ' . $registrationId);
        }
    }

    /**
     * Traiter un paiement échoué
     */
    private function handleFailedPayment($paymentIntent)
    {
        try {
            // Si c'est une chaîne, récupérer l'objet payment_intent de Stripe
            if (is_string($paymentIntent)) {
                $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntent);
            }
            
            // Rechercher l'inscription associée au paiement échoué
            $registration = Registration::where('payment_reference', $paymentIntent->id)->first();

            if ($registration) {
                $registration->payment_status = 'failed';
                $registration->save();
                Log::info('Paiement marqué comme échoué pour l\'inscription #' . $registration->id);
                // Vous pourriez envoyer un email pour informer l'utilisateur de l'échec
            } else {
                Log::warning('Aucune inscription trouvée pour payment_intent: ' . $paymentIntent->id);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement d\'un paiement échoué: ' . $e->getMessage());
        }
    }

    /**
     * Vérifier l'état d'un paiement
     */
    public function checkPaymentStatus(Request $request)
    {
        try {
            $sessionId = $request->session_id;
            
            // Vérifier si le sessionId est présent
            if (empty($sessionId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiant de session manquant',
                ], 400);
            }
            
            try {
                $session = Session::retrieve($sessionId);
                
                // Vérifier si le paiement est complet
                if ($session->payment_status === 'paid') {
                    // Mettre à jour la base de données
                    $registrationId = $session->client_reference_id;
                    $registration = Registration::find($registrationId);
                    
                    if ($registration && $registration->payment_status !== 'completed') {
                        $registration->payment_status = 'completed';
                        $registration->payment_reference = $session->payment_intent;
                        $registration->save();
                        Log::info('Paiement vérifié et marqué comme complété pour l\'inscription #' . $registrationId);
                    }
                    
                    return response()->json([
                        'success' => true,
                        'paid' => true,
                        'status' => 'completed',
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'paid' => false,
                    'status' => $session->payment_status,
                ]);
            } catch (ApiErrorException $e) {
                // Si la session n'existe pas, essayer de vérifier avec l'ID d'enregistrement
                if ($request->registration_id) {
                    $registration = Registration::find($request->registration_id);
                    if ($registration && $registration->payment_status === 'completed') {
                        return response()->json([
                            'success' => true,
                            'paid' => true,
                            'status' => 'completed',
                        ]);
                    }
                }
                
                Log::error('Stripe API Error: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Session de paiement non trouvée ou expirée',
                    'error' => $e->getMessage(),
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Payment Status Check Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du paiement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}