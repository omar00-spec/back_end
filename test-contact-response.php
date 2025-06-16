<?php
// Script de test pour la classe ContactResponse

// Charger l'autoloader de Composer et le framework Laravel
require __DIR__ . '/vendor/autoload.php';

// Charger le framework Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ID du contact à tester (remplacer par un ID valide de votre base de données)
$contactId = 25; // Remplacez par l'ID d'un contact existant

echo "=== Test d'envoi d'email de réponse à un contact ===\n\n";

try {
    // Récupérer le contact
    $contact = App\Models\Contact::find($contactId);
    
    if (!$contact) {
        echo "ERREUR: Contact avec l'ID $contactId non trouvé.\n";
        exit(1);
    }
    
    echo "Contact trouvé:\n";
    echo "ID: " . $contact->id . "\n";
    echo "Nom: " . $contact->name . "\n";
    echo "Email: " . $contact->email . "\n";
    echo "Message: " . $contact->message . "\n\n";
    
    // Message de réponse de test
    $responseText = "Ceci est une réponse de test envoyée le " . date('Y-m-d H:i:s') . 
                   ".\n\nMerci de votre message. Nous sommes heureux de vous répondre.";
    
    echo "Tentative d'envoi d'un email de réponse...\n";
    
    // Créer et envoyer l'email
    $email = new App\Mail\ContactResponse($contact, $responseText);
    $result = Illuminate\Support\Facades\Mail::send($email);
    
    echo "Email de réponse envoyé avec succès!\n";
    
    // Mettre à jour le contact dans la base de données
    $contact->response = $responseText;
    $contact->responded_at = now();
    $contact->read = true;
    $contact->save();
    
    echo "Contact mis à jour dans la base de données.\n";
    
    // Vérifier les logs
    echo "\nVérifiez le fichier de log pour plus de détails: storage/logs/laravel.log\n";
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    
    // Afficher plus de détails sur l'erreur
    echo "\nDétails de l'erreur:\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Fin du test ===\n"; 