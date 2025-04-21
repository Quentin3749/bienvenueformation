<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
session_start();

// Vérification de la session
if (!isset($_SESSION['email'])) {
    // La session n'existe plus, on redirige vers la page de connexion avec un message
    $_SESSION['message_expiration'] = "Votre session a expiré. Veuillez vous reconnecter.";
    header('Location: index.php'); // Assure-toi que "index.php" est le nom de ta page de connexion
    exit();
}


include_once "connect_ddb.php";

class ElevePage {
    private $pdo;
    private $prenom = "Utilisateur inconnu";
    private $userId = null;
    private $classeId = null;
    private $coursJsonAvecStatut = [];
    private $errorMessage = null; // Pour stocker les messages d'erreur

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->chargerInformationsEleve();
        $this->chargerCoursEleve();
    }

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

    private function chargerCoursEleve() {
        if ($this->classeId !== null && $this->userId !== null) {
            try {
                $sqlCoursCalendrierAvecStatut = "
                    SELECT
                        p.Id AS planning_id,
                        m.Name AS title,
                        p.debut_du_cours AS start,
                        p.fin_du_cours AS end,
                        s.statut_presence AS presence_prof
                    FROM planning p
                    JOIN classe c ON p.classe_id = c.Id
                    JOIN matiere m ON p.matiere_id = m.Id
                    LEFT JOIN signature s ON p.Id = s.planning_id AND s.user_id = :user_id
                    WHERE p.classe_id = :classe_id
                    ORDER BY p.debut_du_cours ASC
                ";
                $stmtCoursCalendrierAvecStatut = $this->pdo->prepare($sqlCoursCalendrierAvecStatut);
                $stmtCoursCalendrierAvecStatut->execute(['classe_id' => $this->classeId, 'user_id' => $this->userId]);
                $this->coursJsonAvecStatut = $stmtCoursCalendrierAvecStatut->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $this->errorMessage = "Une erreur est survenue lors du chargement de vos cours.";
                // Optionnel: journaliser l'erreur dans un fichier de log
            }
        }
    }

    public function getPrenom() {
        return htmlspecialchars($this->prenom);
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getClasseId() {
        return $this->classeId;
    }

    public function getCoursJsonEncodedAvecStatut() {
        return json_encode($this->coursJsonAvecStatut);
    }

    public function afficherMessageSession() {
        if (isset($_SESSION['message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['message'] . '</div>';
            unset($_SESSION['message']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if ($this->errorMessage !== null) {
            echo '<div class="alert alert-danger">' . $this->errorMessage . '</div>';
        }
    }

    public function afficherContenuPage() {
        if ($this->userId !== null && $this->classeId !== null) {
            echo '<h2 class="text-center mb-4">Mon Calendrier de Cours</h2>';
            echo '<div id=\'calendar\'></div>';
            // ... (le reste du code pour le modal et la liste des cours) ...
        } else {
            echo '<div class="alert alert-info">Vous êtes connecté. Les informations de votre profil et vos cours seront affichés ici.</div>';
        }
    }

    public function afficherListeCours() {
        if ($this->classeId !== null && $this->userId !== null) {
            echo '<h2 class="text-center mt-5 mb-4">Mes Cours (Liste)</h2>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-striped table-hover">';
            echo '<thead class="table-dark">';
            echo '<tr><th>Classe</th><th>Matière</th><th>Professeur</th><th>Début</th><th>Fin</th><th>Action</th></tr>';
            echo '</thead>';
            echo '<tbody>';
            try {
                $sqlListeCours = "
                    SELECT
                        c.Name AS nom_classe,
                        m.Name AS nom_matiere,
                        CONCAT(u.Nom, ' ', u.Prenom) AS nom_prof,
                        DATE_FORMAT(p.debut_du_cours, '%d/%m/%Y %H:%i') as debut,
                        DATE_FORMAT(p.fin_du_cours, '%d/%m/%Y %H:%i') as fin,
                        p.Id AS planning_id,
                        s.statut_presence AS presence_prof
                    FROM planning p
                    JOIN classe c ON p.classe_id = c.Id
                    JOIN matiere m ON p.matiere_id = m.Id
                    JOIN users u ON p.prof_id = u.IdUsers
                    LEFT JOIN signature s ON p.Id = s.planning_id AND s.user_id = :user_id
                    WHERE p.classe_id = :classe_id
                    ORDER BY p.debut_du_cours ASC
                ";
                $stmtListeCours = $this->pdo->prepare($sqlListeCours);
                $stmtListeCours->execute(['classe_id' => $this->classeId, 'user_id' => $this->userId]);
                while ($rowListeCours = $stmtListeCours->fetch()):
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($rowListeCours['nom_classe']) . '</td>';
                    echo '<td>' . htmlspecialchars($rowListeCours['nom_matiere']) . '</td>';
                    echo '<td>' . htmlspecialchars($rowListeCours['nom_prof']) . '</td>';
                    echo '<td>' . htmlspecialchars($rowListeCours['debut']) . '</td>';
                    echo '<td>' . htmlspecialchars($rowListeCours['fin']) . '</td>';
                    echo '<td>';
                    if ($rowListeCours['presence_prof'] === 'a_confirmer'):
                        echo '<form action="signature_cours.php" method="POST">';
                        echo '<input type="hidden" name="planning_id" value="' . htmlspecialchars($rowListeCours['planning_id']) . '">';
                        echo '<input type="hidden" name="user_id" value="' . htmlspecialchars($this->userId) . '">';
                        echo '<button type="submit" class="btn btn-sm btn-outline-primary">Signer le cours</button>';
                        echo '</form>';
                    elseif ($rowListeCours['presence_prof'] === 'signe'):
                        echo '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Signé</span>';
                    elseif ($rowListeCours['presence_prof'] === 'absent'):
                        echo '<span class="text-danger"><i class="bi bi-x-circle-fill"></i> Absent du cours</span>';
                    elseif ($rowListeCours['presence_prof'] === null):
                        echo '<span class="text-muted"><i class="bi bi-question-circle-fill"></i> Appel non fait</span>';
                    else:
                        echo '<span class="text-warning">';
                        echo '<form action="signature_cours.php" method="POST">';
                        echo '<input type="hidden" name="planning_id" value="' . htmlspecialchars($rowListeCours['planning_id']) . '">';
                        echo '<input type="hidden" name="user_id" value="' . htmlspecialchars($this->userId) . '">';
                        echo '<button type="submit" class="btn btn-sm btn-warning">Confirmer la présence</button>';
                        echo '</form>';
                        echo '</span>';
                    endif;
                    echo '</td>';
                    echo '</tr>';
                endwhile;
            } catch (PDOException $e) {
                echo "<div class='alert alert-danger'>Une erreur est survenue lors de la récupération des cours.</div>";
            }
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        }
    }
}

// On crée un "objet" ElevePage
$pageEleve = new ElevePage($pdo);

// On récupère les données dont on a besoin pour la page
$prenomEleve = $pageEleve->getPrenom();
$coursJsonEncoded = $pageEleve->getCoursJsonEncodedAvecStatut();
$userIdEleve = $pageEleve->getUserId();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/locale/fr.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <title>Élève</title>
    <style>
        .fc-event {
            cursor: pointer; /* Indiquer que l'événement est cliquable */
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-dark-subtle">
    <a class="navbar-brand" href="#">espace élève</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav ms-auto">
            <?php if ($prenomEleve !== "Utilisateur inconnu"): ?>
                <li class="nav-item active">
                    <a class="nav-link" href="#">Bienvenue, <?= $prenomEleve ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Déconnexion</a>
                </li>
            <?php else: ?>
                <li class="nav-item active">
                    <a class="nav-link" href="login.php">Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Link</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<div class="container mt-5">
    <?php $pageEleve->afficherMessageSession(); ?>
    <?php $pageEleve->afficherContenuPage(); ?>

    <?php if ($pageEleve->getUserId() !== null && $pageEleve->getClasseId() !== null): ?>
        <div class="modal fade" id="signatureModal" tabindex="-1" aria-labelledby="signatureModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="signatureModalLabel">Confirmer votre présence</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir confirmer votre présence pour le cours de <strong id="modal-matiere"></strong> du <strong id="modal-date"></strong> ?</p>
                        <form id="signatureForm" action="signature_cours.php" method="POST">
                            <input type="hidden" name="planning_id" id="signature-planning-id">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($userIdEleve) ?>">
                            <button type="submit" class="btn btn-primary">Oui, je confirme ma présence</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    </div>
                </div>
            </div>
        </div>

        <?php $pageEleve->afficherListeCours(); ?>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        $('#calendar').fullCalendar({
            locale: 'fr',
            events: <?= $coursJsonEncoded ?>,
            eventClick: function(calEvent, jsEvent, view) {
                var dateCours = moment(calEvent.start).format('DD/MM/YYYY HH:mm');
                $('#modal-date').text(dateCours);
                $('#modal-matiere').text(calEvent.title);
                $('#signature-planning-id').val(calEvent.planning_id);

                if (calEvent.presence_prof === 'a_confirmer') {
                    var signatureModal = new bootstrap.Modal(document.getElementById('signatureModal'));
                    signatureModal.show();
                } else if (calEvent.presence_prof === 'signe') {
                    alert('Vous avez déjà signé ce cours.');
                } else if (calEvent.presence_prof === 'absent') {
                    alert('Vous êtes marqué absent pour ce cours.');
                } else {
                    alert('L\'appel pour ce cours n\'a pas encore été fait pour vous.');
                }
            }
        });
    });
</script>

</body>
</html>