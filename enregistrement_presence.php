<?php
include_once __DIR__ . '/configuration/connexion_bdd.php';
include_once __DIR__ . '/utilitaires/session.php';
exiger_authentification();

// Active l'affichage des erreurs pour le développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Classe pour gérer l'enregistrement de la présence
class EnregistrementPresence {
    private $pdo;

    /**
     * Constructeur de la classe
     * 
     * @param PDO $pdo Objet PDO pour la connexion à la base de données
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        // Active le mode exception pour les erreurs PDO (utile pour le debug)
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Traite le formulaire de présence
        $this->processFormulaire();
    }

    /**
     * Traite le formulaire de présence
     */
    private function processFormulaire() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Affiche le contenu de $_POST pour le debug
            echo "<pre>";
            var_dump($_POST);
            echo "</pre>";

            // Récupère les données du formulaire
            $classeId = filter_input(INPUT_POST, 'classe_id', FILTER_SANITIZE_NUMBER_INT);
            $courseDate = filter_input(INPUT_POST, 'course_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $planningId = filter_input(INPUT_POST, 'planning_id', FILTER_SANITIZE_NUMBER_INT);
            $presence = $_POST['presence'] ?? [];
            $comment = $_POST['comment'] ?? [];

            if ($classeId && $courseDate && $planningId && is_array($presence) && !empty($presence)) {
                // Enregistre la présence de chaque élève pour le cours
                $this->enregistrerPresences($planningId, $presence, $comment);
            } else {
                $_SESSION['error'] = "Données du formulaire incomplètes ou invalides.";
            }

            // Redirige vers la page professeur après traitement
            header("Location: prof.php");
            exit();
        } else {
            // Si accès direct sans soumission de formulaire
            header("Location: prof.php");
            exit();
        }
    }

    /**
     * Enregistre la présence de chaque élève pour le cours
     * 
     * @param int $planningId ID du planning
     * @param array $presence Tableau des présences
     * @param array $comment Tableau des commentaires
     */
    private function enregistrerPresences($planningId, array $presence, array $comment) {
        try {
            // Démarre une transaction pour les requêtes suivantes
            $this->pdo->beginTransaction();

            foreach ($presence as $userId => $statut) {
                // Prépare la requête pour insérer ou mettre à jour la présence
                $sql = "REPLACE INTO signature (planning_id, user_id, statut_presence, commentaire) VALUES (:planning_id, :user_id, :statut, :commentaire)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'planning_id' => $planningId,
                    'user_id' => $userId,
                    'statut' => $statut,
                    'commentaire' => $comment[$userId] ?? ''
                ]);
            }

            // Valide la transaction
            $this->pdo->commit();
            // Enregistre un message de succès dans la session
            $_SESSION['message'] = "L'appel a été enregistré avec succès.";
        } catch (PDOException $e) {
            // Annule la transaction en cas d'erreur
            $this->pdo->rollBack();
            // Enregistre un message d'erreur dans la session
            $_SESSION['error'] = "Erreur lors de l'enregistrement de la présence : " . $e->getMessage();
            // Affiche l'erreur PDO pour le debug
            echo "Erreur PDO : " . $e->getMessage() . "<br>";
        }
    }
}

// Création de l'objet et traitement du formulaire
$enregistrementPresence = new EnregistrementPresence($pdo);

?>