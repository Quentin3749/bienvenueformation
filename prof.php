<?php
include_once __DIR__ . '/configuration/connexion_bdd.php';
include_once __DIR__ . '/utilitaires/session.php';
exiger_authentification();

// Désactive le cache HTTP pour garantir l'actualisation des données
header("Cache-Control: private, no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// Classe pour gérer la page professeur (affichage des cours, élèves, etc.)
class ProfesseurPage {
    private $pdo;
    private $professeurData = null;
    private $cours = [];
    private $calendarEvents = [];
    private $errorMessage = null; // Pour stocker les messages d'erreur

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->chargerInformationsProfesseur();
        if ($this->professeurData) {
            $this->chargerCours();
            $this->chargerEvenementsCalendrier();
        }
    }
    private function chargerInformationsProfesseur() {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['email'])) {
            $email = $_SESSION['email'];
            try {
                $sql = "SELECT idUsers, prenom FROM users WHERE mail = :email AND role = 'enseignant'";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['email' => $email]);
                $this->professeurData = $stmt->fetch();
            } catch (PDOException $e) {
                $this->errorMessage = "Une erreur est survenue lors de la récupération de vos informations.";
            }
        }
    }
    private function chargerCours() {
        if ($this->professeurData) {
            $prof_id = $this->professeurData['idUsers'];
            try {
                $sql_courses = "
                    SELECT
                        m.Name AS matiere,
                        c.Name AS classe,
                        c.Id AS classe_id,
                        p.Id AS planning_id,
                        p.debut_du_cours,
                        p.fin_du_cours
                    FROM planning p
                    INNER JOIN matiere m ON p.matiere_id = m.Id
                    INNER JOIN classe c ON p.classe_id = c.Id
                    WHERE p.prof_id = :prof_id
                    ORDER BY p.debut_du_cours DESC";
                $stmt_courses = $this->pdo->prepare($sql_courses);
                $stmt_courses->execute(['prof_id' => $prof_id]);
                $this->cours = $stmt_courses->fetchAll();
            } catch (PDOException $e) {
                $this->errorMessage = "Erreur lors de la récupération des cours.";
            }
        }
    }
    private function chargerEvenementsCalendrier() {
        // Cette méthode peut être complétée selon les besoins
    }
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
    public function getCours() {
        return $this->cours;
    }
}

// Création de l'objet ProfesseurPage
$pageProfesseur = new ProfesseurPage($pdo);
$courses = $pageProfesseur->getCours();
$prenom = isset($_SESSION['prenom']) ? $_SESSION['prenom'] : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Professeur</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet" />
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-dark-subtle">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">espace professeur</a>
            <div class="d-flex">
                <a href="logout.php" class="btn btn-outline-danger ms-2">Déconnexion</a>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>
    <div class="container mt-5">
        <?php $pageProfesseur->afficherMessagesSession(); ?>
        <div class="row">
            <div class="col-12 mb-4">
                <div id="calendar"></div>
            </div>
            <div class="col-12">
                <h2>
                    <i class="bi bi-calendar3"></i>
                    Cours de <?= htmlspecialchars($prenom) ?>
                </h2>
                <!-- Tableau des cours du professeur -->
                <?php if (!empty($courses)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Matière</th>
                                    <th>Classe</th>
                                    <th>Date début</th>
                                    <th>Date fin</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $index => $course): ?>
                                    <tr>
                                        <td><i class="bi bi-book"></i> <?= htmlspecialchars($course['matiere']) ?></td>
                                        <td><?= htmlspecialchars($course['classe']) ?></td>
                                        <td><?= htmlspecialchars($course['debut_du_cours']) ?></td>
                                        <td><?= htmlspecialchars($course['fin_du_cours']) ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" onclick="afficherListe(<?= $index ?>)">Faire l'appel</button>
                                            <button class="btn btn-secondary btn-sm d-none" id="masquerBtn<?= $index ?>" onclick="masquerListe(<?= $index ?>)">Masquer la liste</button>
                                        </td>
                                    </tr>
                                    <tr class="d-none" id="liste<?= $index ?>">
                                        <td colspan="5">
                                            <?php include 'attendance_form.php'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="alert alert-info"><i class="bi bi-info-circle"></i> Aucun cours n'est programmé pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'fr',
                events: [
                    <?php foreach ($courses as $c): ?>
                    {
                        title: '<?= addslashes($c['matiere']) ?> (<?= addslashes($c['classe']) ?>)',
                        start: '<?= $c['debut_du_cours'] ?>',
                        end: '<?= $c['fin_du_cours'] ?>'
                    },
                    <?php endforeach; ?>
                ]
            });
            calendar.render();
        });
        function afficherListe(idx) {
            document.getElementById('liste'+idx).classList.remove('d-none');
            document.querySelector('button[onclick="afficherListe('+idx+')"]').classList.add('d-none');
            document.getElementById('masquerBtn'+idx).classList.remove('d-none');
        }
        function masquerListe(idx) {
            document.getElementById('liste'+idx).classList.add('d-none');
            document.querySelector('button[onclick="afficherListe('+idx+')"]').classList.remove('d-none');
            document.getElementById('masquerBtn'+idx).classList.add('d-none');
        }
    </script>
</body>
</html>