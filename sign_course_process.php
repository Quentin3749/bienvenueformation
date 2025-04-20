<?php
session_start();
include_once "connect_ddb.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['planning_id']) && isset($_POST['user_id'])) {
    $planningId = $_POST['planning_id'];
    $userId = $_POST['user_id'];
    $dateSignature = date("Y-m-d H:i:s"); // Date et heure de la signature

    try {
        // Vérifier si une signature existe déjà pour cet utilisateur et ce cours
        $sqlCheckSignature = "SELECT id FROM signature WHERE planning_id = :planning_id AND user_id = :user_id";
        $stmtCheckSignature = $pdo->prepare($sqlCheckSignature);
        $stmtCheckSignature->execute(['planning_id' => $planningId, 'user_id' => $userId]);

        if ($stmtCheckSignature->rowCount() > 0) {
            $_SESSION['message'] = "<div class='alert alert-warning'>Vous avez déjà signé ce cours.</div>";
        } else {
            // Insérer la nouvelle signature avec le statut 'signe' par l'élève
            $sqlInsertSignature = "INSERT INTO signature (planning_id, user_id, date_signature, statut_presence) VALUES (:planning_id, :user_id, :date_signature, 'signe')";
            $stmtInsertSignature = $pdo->prepare($sqlInsertSignature);
            $stmtInsertSignature->execute(['planning_id' => $planningId, 'user_id' => $userId, 'date_signature' => $dateSignature]);

            if ($stmtInsertSignature->rowCount() > 0) {
                $_SESSION['message'] = "<div class='alert alert-success'>Votre présence a été enregistrée avec succès.</div>";
            } else {
                $_SESSION['message'] = "<div class='alert alert-danger'>Erreur lors de l'enregistrement de votre présence. Veuillez réessayer.</div>";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Erreur de base de données : " . $e->getMessage() . "</div>";
        // Optionnel: log l'erreur dans un fichier de logs
    }

    // Rediriger l'utilisateur vers la page de l'élève
    header("Location: eleve.php");
    exit();
} else {
    // Si on accède à ce fichier sans une requête POST valide
    header("Location: eleve.php");
    exit();
}
?>