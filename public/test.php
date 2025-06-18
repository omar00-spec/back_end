<?php

// Afficher des informations de base pour le débogage
echo "<h1>Test de connexion au serveur PHP</h1>";
echo "<p>L'heure actuelle du serveur est: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP version: " . phpversion() . "</p>";

// Afficher les variables d'environnement
echo "<h2>Variables d'environnement</h2>";
echo "<pre>";
$env_vars = getenv();
foreach ($env_vars as $key => $value) {
    // Ne pas afficher les informations sensibles
    if (!in_array(strtolower($key), ['db_password', 'app_key', 'password', 'secret'])) {
        echo "$key: $value\n";
    }
}
echo "</pre>";

// Tester la connexion à la base de données
echo "<h2>Test de connexion à la base de données</h2>";
try {
    $dbhost = getenv('DB_HOST');
    $dbname = getenv('DB_DATABASE');
    $dbuser = getenv('DB_USERNAME');
    $dbpass = getenv('DB_PASSWORD');
    
    if (!$dbhost || !$dbname || !$dbuser) {
        echo "<p style='color:red'>Variables de connexion à la base de données manquantes</p>";
    } else {
        $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p style='color:green'>Connexion à la base de données réussie!</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Erreur de connexion à la base de données: " . $e->getMessage() . "</p>";
}

// Tester les routes API
echo "<h2>Test des routes API</h2>";
echo "<p>Pour tester manuellement les routes API, essayez ces URLs:</p>";
echo "<ul>";
echo "<li><a href='/api/ping' target='_blank'>/api/ping</a> - Devrait retourner {\"message\":\"API OK\"}</li>";
echo "<li><a href='/api/categories' target='_blank'>/api/categories</a> - Devrait lister les catégories</li>";
echo "</ul>";

// Afficher les informations du serveur
echo "<h2>Informations du serveur</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>"; 