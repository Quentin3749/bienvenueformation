<?php
include_once __DIR__ . '/configuration/connexion_bdd.php';
include_once __DIR__ . '/utilitaires/session.php';
exiger_authentification();

// Démarre la session pour gérer les messages utilisateur
session_start();
// Inclut le fichier de connexion à la base de données
include_once "connect_ddb.php";

// Classe pour gérer la signature de présence d'un élève à un cours
class SignatureEleve {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Enregistre la signature de présence de l'élève
    public function enregistrerSignature() {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['planning_id']) && isset($_POST['user_id'])) {
            $planningId = $_POST['planning_id'];
            $userId = $_POST['user_id'];
            $dateSignature = date("Y-m-d H:i:s");

            try {
                // Vérifie si l'élève a déjà signé pour ce cours
                if ($this->signatureExiste($planningId, $userId)) {
                    $_SESSION['message'] = "<div class='alert alert-warning'>Vous avez déjà signé ce cours.</div>";
                } else {
                    // Insère la signature si elle n'existe pas encore
                    if ($this->insererSignature($planningId, $userId, $dateSignature)) {
                        $_SESSION['message'] = "<div class='alert alert-success'>Votre présence a été enregistrée avec succès.</div>";
                    } else {
                        $_SESSION['message'] = "<div class='alert alert-danger'>Erreur lors de l'enregistrement de votre présence. Veuillez réessayer.</div>";
                    }
                }
            } catch (PDOException $e) {
                $_SESSION['message'] = "<div class='alert alert-danger'>Erreur de base de données : " . $e->getMessage() . "</div>";
                // Optionnel: log l'erreur dans un fichier de logs
            }

            // Redirige vers la page élève après traitement
            header("Location: eleve.php");
            exit();
        } else {
            // Redirige si accès direct sans POST
            header("Location: eleve.php");
            exit();
        }
    }

    // Vérifie si la signature existe déjà pour cet élève et ce cours
    private function signatureExiste($planningId, $userId) {
        $sqlCheckSignature = "SELECT id FROM signature WHERE planning_id = :planning_id AND user_id = :user_id";
        $stmtCheckSignature = $this->pdo->prepare($sqlCheckSignature);
        $stmtCheckSignature->execute(['planning_id' => $planningId, 'user_id' => $userId]);
        return $stmtCheckSignature->rowCount() > 0;
    }

    // Insère la signature de présence dans la base de données
    private function insererSignature($planningId, $userId, $dateSignature) {
        $sqlInsertSignature = "INSERT INTO signature (planning_id, user_id, date_signature, statut_presence) VALUES (:planning_id, :user_id, :date_signature, 'signe')";
        $stmtInsertSignature = $this->pdo->prepare($sqlInsertSignature);
        return $stmtInsertSignature->execute(['planning_id' => $planningId, 'user_id' => $userId, 'date_signature' => $dateSignature]);
    }
}

// Création de l'objet et enregistrement de la signature
$signatureEleve = new SignatureEleve($pdo);
$signatureEleve->enregistrerSignature();
?>