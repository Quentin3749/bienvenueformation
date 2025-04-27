<?php
// Script pour qu'un élève signe sa présence à un cours
session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['message_expiration'] = "Votre session a expiré. Veuillez vous reconnecter.";
    header('Location: index.php');
    exit();
}

include_once __DIR__ . '/configuration/connexion_bdd.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['planning_id'])) {
    $planning_id = intval($_POST['planning_id']);
    $email = $_SESSION['email'];

    // Récupérer l'id de l'élève connecté
    $sqlUser = "SELECT IdUsers FROM users WHERE mail = :email AND role = 'etudiant'";
    $stmtUser = $pdo->prepare($sqlUser);
    $stmtUser->execute(['email' => $email]);
    $user = $stmtUser->fetch();

    if ($user) {
        $user_id = $user['IdUsers'];
        // Vérifier si une ligne existe déjà
        $sqlCheck = "SELECT * FROM signature WHERE planning_id = :planning_id AND user_id = :user_id";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([
            'planning_id' => $planning_id,
            'user_id' => $user_id
        ]);
        if ($stmtCheck->rowCount() > 0) {
            // ligne existe, update seulement signature_eleve
            $sqlSign = "UPDATE signature SET signature_eleve = 1 WHERE planning_id = :planning_id AND user_id = :user_id";
            $stmtSign = $pdo->prepare($sqlSign);
            $stmtSign->execute([
                'planning_id' => $planning_id,
                'user_id' => $user_id
            ]);
        } else {
            // ligne n'existe pas, insert avec statut Présent et signature_eleve=1
            $sqlInsert = "INSERT INTO signature (planning_id, user_id, statut_presence, signature_eleve) VALUES (:planning_id, :user_id, 'Présent', 1)";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                'planning_id' => $planning_id,
                'user_id' => $user_id
            ]);
        }
        $_SESSION['message'] = "Votre confirmation de présence a bien été enregistrée.";
    } else {
        $_SESSION['error'] = "Impossible de retrouver votre compte utilisateur.";
    }
} else {
    $_SESSION['error'] = "Requête invalide.";
}

header('Location: eleve.php');
exit();
