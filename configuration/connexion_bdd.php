<?php
/**
 * Connexion à la base de données (centralisée)
 * Utiliser : include_once __DIR__ . '/../configuration/connexion_bdd.php';
 */
$hote = 'localhost';
$nom_bdd = 'bienvenueformation';
$utilisateur = 'root';
$mot_de_passe = '';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
];
try {
    $pdo = new PDO("mysql:host=$hote;dbname=$nom_bdd", $utilisateur, $mot_de_passe, $options);
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}
