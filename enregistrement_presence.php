<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once "connect_ddb.php";

class EnregistrementPresence {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        // Activer l'affichage des erreurs PDO (pour le développement)
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->verifierProfesseur();
        $this->processFormulaire();
    }

    private function verifierProfesseur() {
        if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'enseignant') {
            header("Location: login.php");
            exit();
        }
    }

    private function processFormulaire() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Affiche le contenu de $_POST (pour le développement)
            echo "<pre>";
            var_dump($_POST);
            echo "</pre>";

            // Récupérer les données du formulaire
            $classeId = filter_input(INPUT_POST, 'classe_id', FILTER_SANITIZE_NUMBER_INT);
            $courseDate = filter_input(INPUT_POST, 'course_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $planningId = filter_input(INPUT_POST, 'planning_id', FILTER_SANITIZE_NUMBER_INT);
            $presence = $_POST['presence'] ?? [];
            $comment = $_POST['comment'] ?? [];

            if ($classeId && $courseDate && $planningId && is_array($presence) && !empty($presence)) {
                $this->enregistrerPresences($planningId, $presence, $comment);
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
    }

    private function enregistrerPresences($planningId, array $presence, array $comment) {
        try {
            $this->pdo->beginTransaction();

            foreach ($presence as $userId => $statut) {
                $userId = filter_var($userId, FILTER_SANITIZE_NUMBER_INT);
                $statut = filter_var($statut, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $commentaire = $comment[$userId] ?? ''; // Récupérer le commentaire, vide si non défini

                $existingSignatureId = $this->verifierSignatureExistante($userId, $planningId);

                if ($existingSignatureId) {
                    $this->mettreAJourPresence($existingSignatureId, $statut, $commentaire);
                } else {
                    $this->insererPresence($userId, $planningId, $statut, $commentaire);
                }
            }

            $this->pdo->commit();
            $_SESSION['message'] = "L'appel a été enregistré avec succès.";

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $_SESSION['error'] = "Erreur lors de l'enregistrement : " . $e->getMessage();
            echo "Erreur PDO : " . $e->getMessage() . "<br>"; // Afficher l'erreur PDO
        }
    }

    private function verifierSignatureExistante($userId, $planningId) {
        $sqlCheck = "SELECT id FROM signature WHERE user_id = :user_id AND planning_id = :planning_id";
        $stmtCheck = $this->pdo->prepare($sqlCheck);
        $stmtCheck->execute(['user_id' => $userId, 'planning_id' => $planningId]);
        $existing = $stmtCheck->fetch();
        return $existing ? $existing['id'] : null;
    }

    private function mettreAJourPresence($signatureId, $statut, $commentaire) {
        $sqlUpdate = "UPDATE signature SET statut_presence = :statut, commentaire = :commentaire WHERE id = :id";
        $stmtUpdate = $this->pdo->prepare($sqlUpdate);
        $stmtUpdateResult = $stmtUpdate->execute([
            'statut' => $statut,
            'commentaire' => $commentaire,
            'id' => $signatureId
        ]);
        if (!$stmtUpdateResult) {
            $errorInfo = $stmtUpdate->errorInfo();
            echo "Erreur lors de la mise à jour : " . $errorInfo[2] . "<br>";
        }
    }

    private function insererPresence($userId, $planningId, $statut, $commentaire) {
        $sqlInsert = "INSERT INTO signature (user_id, planning_id, statut_presence, commentaire)
                          VALUES (:user_id, :planning_id, :statut, :commentaire)";
        $stmtInsert = $this->pdo->prepare($sqlInsert);
        $stmtInsertResult = $stmtInsert->execute([
            'user_id' => $userId,
            'planning_id' => $planningId,
            'statut' => $statut,
            'commentaire' => $commentaire
        ]);
        if (!$stmtInsertResult) {
            $errorInfo = $stmtInsert->errorInfo();
            echo "Erreur lors de l'insertion : " . $errorInfo[2] . "<br>";
        }
    }
}

// Création de l'objet et traitement du formulaire
$enregistrementPresence = new EnregistrementPresence($pdo);

?>