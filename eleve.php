<?php
include_once __DIR__ . '/configuration/connexion_bdd.php';
include_once __DIR__ . '/utilitaires/session.php';
exiger_authentification();

// Désactive le cache HTTP pour garantir l'actualisation des données
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Classe pour gérer la page élève (affichage des cours, statut, etc.)
class ElevePage {
    private $pdo;
    private $prenom = "Utilisateur inconnu";
    private $userId = null;
    private $classeId = null;
    private $coursJsonAvecStatut = [];
    private $errorMessage = null; // Pour stocker les messages d'erreur

    /**
     * Constructeur de la classe ElevePage
     * @param PDO $pdo Objet de connexion à la base de données
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->chargerInformationsEleve();
        $this->chargerCoursEleve();
    }

    /**
     * Récupère les informations de l'élève connecté
     */
    private function chargerInformationsEleve() {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['email'])) {
            $email = $_SESSION['email'];
            try {
                $sqlEleve = "SELECT IdUsers, prenom, classe_id FROM users WHERE mail = :email AND role = 'etudiant'";
                $stmtEleve = $this->pdo->prepare($sqlEleve);
                $stmtEleve->execute(['email' => $email]);

                if ($stmtEleve->rowCount() > 0) {
                    $dataEleve = $stmtEleve->fetch();
                    $this->prenom = $dataEleve['prenom'];
                    $this->userId = $dataEleve['IdUsers'];
                    $this->classeId = $dataEleve['classe_id'];
                }
            } catch (PDOException $e) {
                $this->errorMessage = "Une erreur est survenue lors de la récupération de vos informations.";
                // Optionnel: journaliser l'erreur dans un fichier de log
            }
        }
    }

    /**
     * Récupère les cours et le statut de présence de l'élève
     */
    private function chargerCoursEleve() {
        if ($this->classeId !== null && $this->userId !== null) {
            try {
                $sqlCoursCalendrierAvecStatut = "
                    SELECT
                        p.Id AS planning_id,
                        m.Name AS title,
                        p.debut_du_cours AS start,
                        p.fin_du_cours AS end,
                        (
                            SELECT s.statut_presence
                            FROM signature s
                            WHERE s.planning_id = p.Id AND s.user_id = :user_id
                            LIMIT 1
                        ) AS statut_presence,
                        (
                            SELECT s.signature_eleve
                            FROM signature s
                            WHERE s.planning_id = p.Id AND s.user_id = :user_id
                            LIMIT 1
                        ) AS signature_eleve,
                        CONCAT(u.Nom, ' ', u.Prenom) AS professeur
                    FROM planning p
                    INNER JOIN matiere m ON p.matiere_id = m.Id
                    INNER JOIN users u ON p.prof_id = u.IdUsers
                    WHERE p.classe_id = :classe_id
                    ORDER BY p.debut_du_cours DESC
                ";
                $stmtCours = $this->pdo->prepare($sqlCoursCalendrierAvecStatut);
                $stmtCours->execute([
                    'classe_id' => $this->classeId,
                    'user_id' => $this->userId
                ]);
                $this->coursJsonAvecStatut = $stmtCours->fetchAll();
            } catch (PDOException $e) {
                $this->errorMessage = "Erreur lors de la récupération des cours : " . $e->getMessage();
            }
        }
    }

    /**
     * Retourne le prénom de l'élève
     * @return string Prénom de l'élève
     */
    public function getPrenomEleve() {
        return $this->prenom;
    }

    /**
     * Retourne les cours de l'élève avec leur statut
     * @return array Tableau des cours avec leur statut
     */
    public function getCoursJsonAvecStatut() {
        return $this->coursJsonAvecStatut;
    }

    /**
     * Affiche les messages de session ou d'erreur
     */
    public function afficherMessagesSession() {
        if (isset($_SESSION['message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['message'] . '</div>';
            unset($_SESSION['message']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if ($this->errorMessage) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($this->errorMessage) . '</div>';
        }
    }
}

// On crée un "objet" ElevePage
$pageEleve = new ElevePage($pdo);

// On récupère les données dont on a besoin pour la page
$prenom = $pageEleve->getPrenomEleve();
$coursJson = $pageEleve->getCoursJsonAvecStatut();
$coursJsonEncoded = json_encode($coursJson);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Élève</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-dark-subtle">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Espace élève</a>
            <div class="d-flex">
                <a href="logout.php" class="btn btn-outline-danger ms-2">Déconnexion</a>
            </div>
        </div>
    </nav>
    <div class="container mt-5">
        <?php $pageEleve->afficherMessagesSession(); ?>
        <h2>Bienvenue, <?= htmlspecialchars($prenom) ?></h2>
        <!-- Calendrier des cours de l'élève -->
        <div id="calendar"></div>
        <!-- Tableau des cours de l'élève -->
        <?php if (!empty($coursJson)): ?>
        <div class="table-responsive mt-4">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Matière</th>
                        <th>Professeur</th>
                        <th>Date début</th>
                        <th>Date fin</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coursJson as $cours): ?>
                        <tr>
                            <td><?= htmlspecialchars($cours['title']) ?></td>
                            <td><?= htmlspecialchars($cours['professeur']) ?></td>
                            <td><?= htmlspecialchars($cours['start']) ?></td>
                            <td><?= htmlspecialchars($cours['end']) ?></td>
                            <td>
                                <?php
                                $statut = $cours['statut_presence'] ?? '';
                                echo $statut ? htmlspecialchars($statut) : '<span class="text-muted">Non renseigné</span>';
                                ?>
                            </td>
                            <td>
                                <?php
                                $statut = $cours['statut_presence'] ?? '';
                                $signature_eleve = $cours['signature_eleve'] ?? 0;
                                if (($statut === 'Présent' || $statut === 'present') && $signature_eleve != 1) {
                                ?>
                                    <form action="sign_presence.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="planning_id" value="<?= htmlspecialchars($cours['planning_id']) ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Signer présence</button>
                                    </form>
                                <?php } elseif ($signature_eleve == 1 && ($statut === 'Présent' || $statut === 'present')) {
                                    echo '<span class="text-success">Présence signée</span>';
                                } else {
                                    echo '<span class="text-muted">Non renseigné</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="alert alert-info mt-4">Aucun cours programmé pour votre classe.</p>
        <?php endif; ?>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/locale/fr.js"></script>
    <script>
        $(document).ready(function() {
            $('#calendar').fullCalendar({
                locale: 'fr',
                events: <?= $coursJsonEncoded ?>,
                eventRender: function(event, element) {
                    if (event.statut_presence) {
                        element.append('<span class="badge bg-info ms-2">' + event.statut_presence + '</span>');
                    }
                }
            });
        });
    </script>
</body>
</html>