<?php
header("Cache-Control: private, no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
session_start();


if (!isset($_SESSION['email'])) {
    // La session n'existe plus, on redirige vers la page de connexion avec un message
    $_SESSION['message_expiration'] = "Votre session a expiré. Veuillez vous reconnecter.";
    header('Location: index.php'); // Assure-toi que "index.php" est le nom de ta page de connexion
    exit();
}
include_once "connect_ddb.php";

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
                // Optionnel: journaliser l'erreur dans un fichier de log
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
                    ORDER BY p.debut_du_cours";
                $stmt_courses = $this->pdo->prepare($sql_courses);
                $stmt_courses->execute(['prof_id' => $prof_id]);
                $this->cours = $stmt_courses->fetchAll();
            } catch (PDOException $e) {
                $this->errorMessage = "Une erreur est survenue lors du chargement de vos cours.";
                // Optionnel: journaliser l'erreur dans un fichier de log
            }
        }
    }

    private function chargerEvenementsCalendrier() {
        if ($this->professeurData) {
            $prof_id = $this->professeurData['idUsers'];
            try {
                $sql_calendar = "
                    SELECT
                        m.Name AS title,
                        p.debut_du_cours AS start,
                        p.fin_du_cours AS end
                    FROM planning p
                    INNER JOIN matiere m ON p.matiere_id = m.Id
                    WHERE p.prof_id = :prof_id
                    ORDER BY p.debut_du_cours";
                $stmt_calendar = $this->pdo->prepare($sql_calendar);
                $stmt_calendar->execute(['prof_id' => $prof_id]);
                $this->calendarEvents = $stmt_calendar->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $this->errorMessage = "Une erreur est survenue lors du chargement des événements du calendrier.";
                // Optionnel: journaliser l'erreur dans un fichier de log
            }
        }
    }

    public function getPrenomProfesseur() {
        return $this->professeurData ? htmlspecialchars($this->professeurData['prenom']) : "Utilisateur inconnu";
    }

    public function getCoursProfesseur() {
        return $this->cours;
    }

    public function getEvenementsCalendrierProfesseur() {
        return json_encode($this->calendarEvents);
    }

    public function afficherMessagesSession() {
        if (isset($_SESSION['message'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($_SESSION['message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION['message']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($_SESSION['error']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION['error']);
        }
        if ($this->errorMessage !== null) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($this->errorMessage);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
    }
}

// Création de l'objet ProfesseurPage
$pageProfesseur = new ProfesseurPage($pdo);

// Récupération des données via l'objet
$prenom = $pageProfesseur->getPrenomProfesseur();
$courses = $pageProfesseur->getCoursProfesseur();
$calendarEventsJSON = $pageProfesseur->getEvenementsCalendrierProfesseur();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Professeur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
    <style>
        .student-list {
            display: none;
        }
        .presence-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .table th {
            background-color: #f1f1f1;
        }
        #calendar {
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-dark-subtle">
        <div class="container-fluid">
        <a class="navbar-brand" href="#">espace élève</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto">
                    <?php if ($prenom !== "Utilisateur inconnu"): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-person-circle"></i> <?= $prenom ?>
                            </a>
                        </li>
                        <li class="nav-item">
                        <a class="nav-link" href="logout.php">Déconnexion</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item active">
                    <a class="nav-link" href="login.php">Login</a>
                </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <?php $pageProfesseur->afficherMessagesSession(); ?>
        <div class="row">
            <div class="col-12">
                <h2>
                    <i class="bi bi-calendar3"></i>
                    Cours de <?= $prenom ?>
                </h2>

                <div id="calendar"></div>

                <?php if (!empty($courses)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Matière</th>
                                    <th>Classe</th>
                                    <th>Début du cours</th>
                                    <th>Fin du cours</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $index => $course): ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-book"></i>
                                            <?= htmlspecialchars($course['matiere']) ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-people"></i>
                                            <?= htmlspecialchars($course['classe']) ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-clock"></i>
                                            <?= htmlspecialchars($course['debut_du_cours']) ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-clock-history"></i>
                                            <?= htmlspecialchars($course['fin_du_cours']) ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary toggle-students" data-index="<?= $index ?>">
                                                <i class="bi bi-clipboard-check"></i> Faire l'appel
                                            </button>
                                        </td>
                                    </tr>
                                    <tr id="students-<?= $index ?>" class="student-list">
                                        <td colspan="5" class="presence-form">
                                            <?php
                                            $classe_id = $course['classe_id'];

                                            // Récupération des élèves de la classe
                                            try {
                                                $sql_students = "
                                                    SELECT idUsers, Nom, prenom
                                                    FROM users
                                                    WHERE classe_id = :classe_id
                                                    AND role = 'etudiant'
                                                    ORDER BY Nom, prenom";
                                                $stmt_students = $pdo->prepare($sql_students);
                                                $stmt_students->execute(['classe_id' => $classe_id]);
                                                $students = $stmt_students->fetchAll();

                                                if (!empty($students)): ?>
                                                    <form action="enregistrement_presence.php" method="POST" class="attendance-form">
                                                        <input type="hidden" name="classe_id" value="<?= htmlspecialchars($classe_id) ?>">
                                                        <input type="hidden" name="course_date" value="<?= htmlspecialchars($course['debut_du_cours']) ?>">
                                                        <input type="hidden" name="planning_id" value="<?= htmlspecialchars($course['planning_id']) ?>">

                                                        <table class="table table-striped">
                                                            <thead>
                                                                <tr>
                                                                    <th>Nom</th>
                                                                    <th>Prénom</th>
                                                                    <th class="text-center">Présent</th>
                                                                    <th class="text-center">Retard</th>
                                                                    <th class="text-center">Absent</th>
                                                                    <th>Commentaire</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($students as $student): ?>
                                                                    <tr>
                                                                        <td><?= htmlspecialchars($student['Nom']) ?></td>
                                                                        <td><?= htmlspecialchars($student['prenom']) ?></td>
                                                                        <td class="text-center">
                                                                            <input type="radio"
                                                                                   name="presence[<?= $student['idUsers'] ?>]"
                                                                                   value="present"
                                                                                   class="form-check-input"
                                                                                   checked>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <input type="radio"
                                                                                   name="presence[<?= $student['idUsers'] ?>]"
                                                                                   value="retard"
                                                                                   class="form-check-input">
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <input type="radio"
                                                                                   name="presence[<?= $student['idUsers'] ?>]"
                                                                                   value="absent"
                                                                                   class="form-check-input">
                                                                        </td>
                                                                        <td>
                                                                            <input type="text"
                                                                                   name="comment[<?= $student['idUsers'] ?>]"
                                                                                   class="form-control form-control-sm"
                                                                                   placeholder="Commentaire optionnel">
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                        <div class="text-end mb-3">
                                                            <button type="submit" class="btn btn-success">
                                                                <i class="bi bi-check-lg"></i> Enregistrer l'appel
                                                            </button>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <div class="alert alert-info">
                                                        <i class="bi bi-info-circle"></i>
                                                        Aucun étudiant trouvé pour cette classe.
                                                    </div>
                                                <?php endif;
                                            } catch (PDOException $e) {
                                                echo "<div class='alert alert-danger'>Erreur lors de la récupération des étudiants.</div>";
                                            }
                                            ?>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/locale/fr.js"></script>
    <script>
        $(document).ready(function() {
            $('#calendar').fullCalendar({
                locale: 'fr',
                events: <?= $calendarEventsJSON ?>
            });

            $('.toggle-students').click(function() {
                var index = $(this).data('index');
                $('#students-' + index).toggle();
            });
        });
    </script>
</body>
</html>