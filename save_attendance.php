<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once "connect_ddb.php";

// Activer l'affichage des erreurs PDO (pour le développement)
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Vérifier si l'utilisateur est un professeur authentifié
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'enseignant') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Affiche le contenu de $_POST
    echo "<pre>";
    var_dump($_POST);
    echo "</pre>";

    // Récupérer les données du formulaire
    $classe_id = filter_input(INPUT_POST, 'classe_id', FILTER_SANITIZE_NUMBER_INT);
    $course_date = filter_input(INPUT_POST, 'course_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $planning_id = filter_input(INPUT_POST, 'planning_id', FILTER_SANITIZE_NUMBER_INT);
    $presence = $_POST['presence'] ?? [];
    $comment = $_POST['comment'] ?? [];

    if ($classe_id && $course_date && $planning_id && is_array($presence) && !empty($presence)) {
        try {
            $pdo->beginTransaction();

            foreach ($presence as $user_id => $statut) {
                $user_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);
                $statut = filter_var($statut, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

                $statut_signature = $statut; // Utiliser directement le statut

                // Rechercher si une entrée existe déjà
                $sql_check = "SELECT id FROM signature WHERE user_id = :user_id AND planning_id = :planning_id";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->execute(['user_id' => $user_id, 'planning_id' => $planning_id]);
                $existing = $stmt_check->fetch();

                if ($existing) {
                    // Mise à jour
                    $sql_update = "UPDATE signature SET statut_presence = :statut WHERE id = :id";
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update_result = $stmt_update->execute([
                        'statut' => $statut_signature,
                        'id' => $existing['id']
                    ]);
                    if (!$stmt_update_result) {
                        $errorInfo = $stmt_update->errorInfo();
                        echo "Erreur lors de la mise à jour : " . $errorInfo[2] . "<br>";
                    }
                } else {
                    // Insertion
                    $sql_insert = "INSERT INTO signature (user_id, planning_id, statut_presence)
                                        VALUES (:user_id, :planning_id, :statut)";
                    $stmt_insert = $pdo->prepare($sql_insert);
                    $stmt_insert_result = $stmt_insert->execute([
                        'user_id' => $user_id,
                        'planning_id' => $planning_id,
                        'statut' => $statut_signature
                    ]);
                    if (!$stmt_insert_result) {
                        $errorInfo = $stmt_insert->errorInfo();
                        echo "Erreur lors de l'insertion : " . $errorInfo[2] . "<br>";
                    }
                }
            }

            $pdo->commit();
            $_SESSION['message'] = "L'appel a été enregistré avec succès.";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Erreur lors de l'enregistrement : " . $e->getMessage();
            echo "Erreur PDO : " . $e->getMessage() . "<br>"; // Afficher l'erreur PDO
        }
    } else {
        $_SESSION['error'] = "Données du formulaire incomplètes ou invalides.";
    }

    // Redirection
    header("Location: prof.php");
    exit();
} else {
    // Si accès direct sans soumission de formulaire
    header("Location: prof.php");
    exit();
}
?>