<?php
// Script de test d'envoi d'email avec Laravel

// Charger l'autoloader de Composer et le framework Laravel
require __DIR__ . '/vendor/autoload.php';

// Charger le framework Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Adresse email de test
$toEmail = 'omarelkhairi67@gmail.com'; // Remplacez par l'email où vous voulez recevoir le test

echo "=== Test d'envoi d'email avec Laravel ===\n\n";
echo "Configuration actuelle:\n";
echo "MAIL_MAILER: " . config('mail.mailer') . "\n";
echo "MAIL_HOST: " . config('mail.host') . "\n";
echo "MAIL_PORT: " . config('mail.port') . "\n";
echo "MAIL_USERNAME: " . config('mail.username') . "\n";
echo "MAIL_ENCRYPTION: " . config('mail.encryption') . "\n";
echo "MAIL_FROM_ADDRESS: " . config('mail.from.address') . "\n";
echo "MAIL_FROM_NAME: " . config('mail.from.name') . "\n\n";

try {
    echo "Tentative d'envoi d'un email à $toEmail...\n";
    
    // Envoyer un email simple
    $result = Illuminate\Support\Facades\Mail::raw(
        'Ceci est un message de test envoyé depuis Laravel à ' . date('Y-m-d H:i:s'),
        function ($message) use ($toEmail) {
            $message->to($toEmail)
                    ->subject('Test d\'envoi d\'email depuis ACOS Football Academy');
        }
    );
    
    echo "Email envoyé avec succès!\n";
    
    // Vérifier les logs pour plus de détails
    echo "\nVérifiez le fichier de log pour plus de détails: storage/logs/laravel.log\n";
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    
    // Afficher plus de détails sur l'erreur
    echo "\nDétails de l'erreur:\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Fin du test ===\n"; 