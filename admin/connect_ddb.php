<?php
// Connexion à la base de données
$host = 'localhost'; 
$db   = 'bienvenueformation';
$user = 'root';
$pass = '';
$charset = 'utf8mb4'; 
$dsn = "mysql:host=$host;dbname=$db";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Connexion échouée : ' . $e->getMessage());
}


?>
