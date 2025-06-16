<?php
// Script de test de connexion SMTP pour déboguer les problèmes d'envoi d'email

// Charger l'autoloader de Composer
require __DIR__ . '/vendor/autoload.php';

// Informations de connexion SMTP
$smtpHost = 'smtp.gmail.com';
$smtpPort = 587;
$smtpUsername = 'omarelkhairi67@gmail.com'; // Remplacez par votre email
$smtpPassword = 'deaixwgtrnjhwla'; // Remplacez par votre mot de passe d'application
$encryption = 'tls';

// Adresse email de test
$toEmail = 'omarelkhairi67@gmail.com'; // Remplacez par l'email où vous voulez recevoir le test

echo "=== Test de connexion SMTP ===\n\n";
echo "Tentative de connexion à $smtpHost:$smtpPort...\n";

try {
    // Créer un transport SMTP
    $transport = new Swift_SmtpTransport($smtpHost, $smtpPort, $encryption);
    $transport->setUsername($smtpUsername);
    $transport->setPassword($smtpPassword);
    
    // Tester la connexion
    echo "Tentative d'authentification...\n";
    $transport->start();
    echo "Connexion SMTP réussie!\n\n";
    
    // Créer un mailer
    $mailer = new Swift_Mailer($transport);
    
    // Créer un message
    echo "Création du message de test...\n";
    $message = new Swift_Message('Test de connexion SMTP depuis ACOS Football Academy');
    $message->setFrom([$smtpUsername => 'ACOS Football Academy']);
    $message->setTo([$toEmail]);
    $message->setBody('Ceci est un message de test pour vérifier la configuration SMTP. Si vous recevez ce message, la configuration est correcte!');
    
    // Envoyer le message
    echo "Envoi du message...\n";
    $result = $mailer->send($message);
    
    if ($result) {
        echo "Message envoyé avec succès à $toEmail!\n";
    } else {
        echo "Échec de l'envoi du message.\n";
    }
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    
    // Afficher plus de détails sur l'erreur
    echo "\nDétails de l'erreur:\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Fin du test ===\n"; 