<?php
// Connexion à la base de données
// Définition des paramètres de connexion
$host = 'localhost'; 
$db   = 'bienvenueformation';
$user = 'root';
$pass = '';
$charset = 'utf8mb4'; 

// Construction de la chaîne de connexion DSN
$dsn = "mysql:host=$host;dbname=$db";

// Définition des options de connexion PDO
$options = [
    // Active le mode exception pour les erreurs PDO
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Définit le mode de récupération par défaut en tableau associatif
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Désactive l'émulation des requêtes préparées
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Tentative de connexion à la base de données
try {
    // Crée une nouvelle connexion PDO avec les paramètres définis
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Affiche un message d'erreur si la connexion échoue
    die('Connexion échouée : ' . $e->getMessage());
}


?>
