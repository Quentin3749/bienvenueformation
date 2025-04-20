<?php
session_start();
include_once "connect_ddb.php";

// Récupérer les informations de l'élève
$prenom = "Utilisateur inconnu";
$userId = null;
$classeId = null;
$coursJsonAvecStatut = []; // Initialisation du tableau pour les cours du calendrier avec statut

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    try {
        $sqlEleve = "SELECT IdUsers, prenom, classe_id FROM users WHERE mail = :email AND role = 'etudiant'";
        $stmtEleve = $pdo->prepare($sqlEleve);
        $stmtEleve->execute(['email' => $email]);

        if ($stmtEleve->rowCount() > 0) {
            $dataEleve = $stmtEleve->fetch();
            $prenom = $dataEleve['prenom'];
            $userId = $dataEleve['IdUsers'];
            $classeId = $dataEleve['classe_id'];

            // Récupérer les cours pour le calendrier de l'élève AVEC l'ID du planning et le statut de présence
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
            $stmtCoursCalendrierAvecStatut = $pdo->prepare($sqlCoursCalendrierAvecStatut);
            $stmtCoursCalendrierAvecStatut->execute(['classe_id' => $classeId, 'user_id' => $userId]);
            $coursJsonAvecStatut = $stmtCoursCalendrierAvecStatut->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $errorMessage = "Erreur lors de la récupération des informations.";
        // Optionnel: log l'erreur
    }
}

// Encoder les données des cours pour le calendrier au format JSON
$coursJsonEncodedAvecStatut = json_encode($coursJsonAvecStatut);
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
    <title>Él&egrave;ve</title>
    <style>
        .fc-event {
            cursor: pointer; /* Indiquer que l'événement est cliquable */
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-dark-subtle">
    <a class="navbar-brand" href="#">Navbar</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav ms-auto">
            <?php if ($prenom !== "Utilisateur inconnu"): ?>
                <li class="nav-item active">
                    <a class="nav-link" href="#">Bienvenue, <?= htmlspecialchars($prenom) ?></a>
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
    <?php if (isset($_SESSION['message'])): ?>
        <?= $_SESSION['message'] ?>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <?= $_SESSION['error'] ?>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($userId) && isset($classeId)): ?>
        <h2 class="text-center mb-4">Mon Calendrier de Cours</h2>
        <div id='calendar'></div>

        <div class="modal fade" id="signatureModal" tabindex="-1" aria-labelledby="signatureModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="signatureModalLabel">Confirmer votre présence</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir confirmer votre présence pour le cours de <strong id="modal-matiere"></strong> du <strong id="modal-date"></strong> ?</p>
                        <form id="signatureForm" action="sign_course_process.php" method="POST">
                            <input type="hidden" name="planning_id" id="signature-planning-id">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">
                            <button type="submit" class="btn btn-primary">Oui, je confirme ma présence</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="text-center mt-5 mb-4">Mes Cours (Liste)</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Classe</th>
                        <th>Matière</th>
                        <th>Professeur</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
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
                        $stmtListeCours = $pdo->prepare($sqlListeCours);
                        $stmtListeCours->execute(['classe_id' => $classeId, 'user_id' => $userId]);
                        while ($rowListeCours = $stmtListeCours->fetch()): ?>
                            <tr>
                                <td><?= htmlspecialchars($rowListeCours['nom_classe']) ?></td>
                                <td><?= htmlspecialchars($rowListeCours['nom_matiere']) ?></td>
                                <td><?= htmlspecialchars($rowListeCours['nom_prof']) ?></td>
                                <td><?= htmlspecialchars($rowListeCours['debut']) ?></td>
                                <td><?= htmlspecialchars($rowListeCours['fin']) ?></td>
                                <td>
                                    <?php
                                    if ($rowListeCours['presence_prof'] === 'a_confirmer'): ?>
                                        <form action="sign_course_process.php" method="POST">
                                            <input type="hidden" name="planning_id" value="<?= htmlspecialchars($rowListeCours['planning_id']) ?>">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Signer le cours</button>
                                        </form>
                                    <?php elseif ($rowListeCours['presence_prof'] === 'signe'): ?>
                                        <span class="text-success"><i class="bi bi-check-circle-fill"></i> Signé</span>
                                    <?php elseif ($rowListeCours['presence_prof'] === 'absent'): ?>
                                        <span class="text-danger"><i class="bi bi-x-circle-fill"></i> Absent du cours</span>
                                    <?php elseif ($rowListeCours['presence_prof'] === null): ?>
                                        <span class="text-muted"><i class="bi bi-question-circle-fill"></i> Appel non fait</span>
                                    <?php else: ?>
                                        <span class="text-warning">
                                            <form action="sign_course_process.php" method="POST">
                                                <input type="hidden" name="planning_id" value="<?= htmlspecialchars($rowListeCours['planning_id']) ?>">
                                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">
                                                <button type="submit" class="btn btn-sm btn-warning">Confirmer la présence</button>
                                            </form>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile;
                    } catch (PDOException $e) {
                        echo "<div class='alert alert-danger'>Une erreur est survenue lors de la récupération des cours.</div>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="alert alert-success">
            vous etes connecté
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        $('#calendar').fullCalendar({
            locale: 'fr',
            events: <?= $coursJsonEncodedAvecStatut ?>,
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