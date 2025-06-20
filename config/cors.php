<?php

return [

'paths' => ['api/*', 'sanctum/csrf-cookie'],

'allowed_methods' => ['*'],

'allowed_origins' => [
    'http://localhost:3000',
    'http://localhost:5173',
    'http://localhost:3001',
    'https://heroic-gaufre-c8e8ae.netlify.app',
    'https://acos-football.netlify.app',
],

// Tu peux garder ceci si tu veux autoriser tous les sous-domaines .netlify.app
'allowed_origins_patterns' => [
    '#^https://.*\.netlify\.app$#',
],

'allowed_headers' => ['*'],

'exposed_headers' => [],

'max_age' => 0,

'supports_credentials' => false,
];
