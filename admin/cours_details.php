<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require_once 'connect_ddb.php';

class GestionCoursAdminDetails {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getCoursDetails($planning_id) {
        $sql = "SELECT p.Id, c.Name AS Classe, m.Name AS Matiere, CONCAT(prof.Nom, ' ', prof.Prenom) AS Professeur, p.debut_du_cours, p.fin_du_cours
                FROM planning p
                JOIN classe c ON p.classe_id = c.Id
                JOIN matiere m ON p.matiere_id = m.Id
                JOIN users prof ON p.prof_id = prof.IdUsers
                WHERE p.Id = :planning_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['planning_id' => $planning_id]);
        return $stmt->fetch();
    }

    public function getElevesStatutsParCours($planning_id) {
        $sql = "SELECT
                    u.idUsers,
                    u.Nom AS EleveNom,
                    u.Prenom AS ElevePrenom,
                    s.statut_presence
                FROM users u
                JOIN classe c ON u.classe_id = c.Id
                LEFT JOIN signature s ON u.idUsers = s.user_id AND s.planning_id = :planning_id1
                WHERE u.role = 'etudiant'
                  AND u.classe_id = (SELECT classe_id FROM planning WHERE Id = :planning_id2)
                ORDER BY u.Nom, u.Prenom";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['planning_id1' => $planning_id, 'planning_id2' => $planning_id]);
        return $stmt->fetchAll();
    }
}

$gestionCoursAdminDetails = new GestionCoursAdminDetails($pdo);

if (isset($_GET['id'])) {
    $planning_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    if ($planning_id) {
        $coursDetails = $gestionCoursAdminDetails->getCoursDetails($planning_id);
        $elevesStatuts = $gestionCoursAdminDetails->getElevesStatutsParCours($planning_id);

        if (!$coursDetails) {
            // Gérer le cas où l'ID du cours n'est pas valide
            header("Location: coursadmin.php");
            exit();
        }
    } else {
        // Gérer le cas où l'ID n'est pas fourni
        header("Location: coursadmin.php");
        exit();
    }
} else {
    // Gérer le cas où l'ID n'est pas fourni
    header("Location: coursadmin.php");
    exit();
}

include "barrenav.php";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <title>Détails du Cours</title>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Détails du Cours</h1>

    <?php if ($coursDetails): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($coursDetails['Matiere']) ?> - <?= htmlspecialchars($coursDetails['Classe']) ?></h5>
                <p class="card-text">Professeur: <?= htmlspecialchars($coursDetails['Professeur']) ?></p>
                <p class="card-text">Début: <?= htmlspecialchars($coursDetails['debut_du_cours']) ?></p>
                <p class="card-text">Fin: <?= htmlspecialchars($coursDetails['fin_du_cours']) ?></p>
                <a href="coursadmin.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left-fill"></i> Retour à la liste des cours</a>
            </div>
        </div>

        <h2>Liste des Élèves et leur Statut</h2>
        <div class="table-responsive">
            <table class='table table-bordered table-hover'>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Statut de Présence</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($elevesStatuts)): ?>
                        <?php foreach ($elevesStatuts as $eleve) {
                            echo "<tr>
                                <td>{$eleve['EleveNom']}</td>
                                <td>{$eleve['ElevePrenom']}</td>
                                <td>";
                            if ($eleve['statut_presence'] === 'present') {
                                echo "<span class='text-success'><i class='bi bi-check-circle-fill'></i> Présent</span>";
                            } elseif ($eleve['statut_presence'] === 'absent') {
                                echo "<span class='text-danger'><i class='bi bi-x-circle-fill'></i> Absent</span>";
                            } elseif ($eleve['statut_presence'] === 'retard') {
                                echo "<span class='text-warning'><i class='bi bi-clock-fill'></i> Retard</span>";
                            } else {
                                echo "<span class='text-muted'><i class='bi bi-question-circle-fill'></i> Non défini</span>";
                            }
                            echo "</td></tr>";
                        } ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center">Aucun élève inscrit dans cette classe.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">Cours non trouvé.</div>
        <a href="coursadmin.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left-fill"></i> Retour à la liste des cours</a>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>