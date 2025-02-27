<?php
include_once "connect_ddb.php"; // Vérifiez que la connexion fonctionne correctement

// Vérifiez si une demande de suppression a été envoyée
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']); // Convertir en entier pour sécuriser l'entrée

    // Préparer et exécuter la requête de suppression
    $sql = "DELETE FROM planning WHERE Id = :id"; // Utilisez Id et non id (erreur de casse)
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute(['id' => $deleteId])) {
        // Rediriger avec succès
        header("Location: coursadmin.php?msg=success");
        exit();
    } else {
        // Rediriger avec un message d'erreur
        header("Location: coursadmin.php?msg=error");
        exit();
    }
} else {
    // Si aucun ID n'est fourni, redirection directe
    header("Location: coursadmin.php?msg=invalid_id");
    exit();
}
?>

