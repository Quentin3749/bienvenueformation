


<?php
include_once "connect_ddb.php";

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];  
    
    try {
        // Supprimer d'abord les enregistrements dans `planning` liés à l'utilisateur
        $sql_planning = "DELETE FROM planning WHERE prof_id = :id";
        $stmt_planning = $pdo->prepare($sql_planning);
        $stmt_planning->execute(['id' => $user_id]);

        // Ensuite, supprimer l'utilisateur
        $sql_user = "DELETE FROM users WHERE IdUsers = :id";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute(['id' => $user_id]);

        if ($stmt_user) {
            header("location:admin.php?message=DeleteSuccess");
        } else {
            header("location:admin.php?message=DeleteFail");
        }
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
} else {
    header("location:admin.php?message=NoID");
}
?>






