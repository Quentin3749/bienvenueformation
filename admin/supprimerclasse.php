<?php
include_once __DIR__ . '/../configuration/connexion_bdd.php';
include_once __DIR__ . '/../utilitaires/session.php';
exiger_authentification();

include_once "connect_ddb.php"; // Vérifiez que la connexion fonctionne correctement

// Vérifiez si une demande de suppression a été envoyée
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']); // Convertir en entier pour sécuriser l'entrée

    // Préparer et exécuter la requête de suppression
    $sql = "DELETE FROM classe WHERE id = :id";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute(['id' => $deleteId])) {
        // Rediriger avec succès
        header("Location: classeadmin.php?msg=success");
        exit();
    } else {
        // Rediriger avec un message d'erreur
        header("Location: classeadmin.php?msg=error");
        exit();
    }
} else {
    // Si aucun ID n'est fourni, redirection directe
    header("Location: classeadmin.php");
    exit();
}
?>
