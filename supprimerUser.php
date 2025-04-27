<?php
// Inclusion centralisée de la connexion à la base de données
include_once __DIR__ . '/configuration/connexion_bdd.php';
// Inclusion de la gestion de session
include_once __DIR__ . '/utilitaires/session.php';
// Vérifie que l'utilisateur est connecté
exiger_authentification();

// Récupère l'identifiant de l'utilisateur à supprimer depuis l'URL
if (isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    // Prépare et exécute la suppression dans la base de données
    $sql = "DELETE FROM users WHERE IdUsers = :id";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute(['id' => $userId])) {
        header('Location: admin.php?message=Utilisateur supprimé avec succès.');
        exit();
    } else {
        header('Location: admin.php?message=Erreur lors de la suppression de l\'utilisateur.');
        exit();
    }
} else {
    header('Location: admin.php?message=Aucun utilisateur spécifié.');
    exit();
}
?>
