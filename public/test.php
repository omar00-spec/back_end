<?php
// Désactiver tous les mécanismes de mise en cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Informations de base sur l'environnement
$info = [
    'status' => 'ok',
    'message' => 'Le serveur PHP fonctionne correctement',
    'php_version' => phpversion(),
    'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Non disponible',
    'time' => date('Y-m-d H:i:s'),
    'environment' => [
        'app_env' => getenv('APP_ENV') ?: 'Non défini',
        'app_debug' => getenv('APP_DEBUG') ?: 'Non défini',
        'app_url' => getenv('APP_URL') ?: 'Non défini',
    ],
    'server_variables' => [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'Non disponible',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'Non disponible',
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'Non disponible',
        'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? 'Non disponible',
        'SERVER_PROTOCOL' => $_SERVER['SERVER_PROTOCOL'] ?? 'Non disponible',
    ]
];

// Afficher les informations au format JSON
echo json_encode($info, JSON_PRETTY_PRINT); 