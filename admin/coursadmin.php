<?php
include_once __DIR__ . '/../configuration/connexion_bdd.php';
include_once __DIR__ . '/../utilitaires/session.php';
exiger_authentification();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require_once 'connect_ddb.php';

class GestionCoursAdmin {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getClasses() {
        $stmt = $this->pdo->query("SELECT Id, Name FROM classe");
        return $stmt->fetchAll();
    }

    public function getMatieres() {
        $stmt = $this->pdo->query("SELECT Id, Name FROM matiere");
        return $stmt->fetchAll();
    }

    public function getProfesseurs() {
        $stmt = $this->pdo->query("SELECT IdUsers, CONCAT(Nom, ' ', Prenom) AS FullName FROM users WHERE role = 'enseignant'");
        return $stmt->fetchAll();
    }

    public function creerCours($classe_id, $matiere_id, $prof_id, $debut, $fin) {
        $sql = "INSERT INTO planning (classe_id, matiere_id, prof_id, debut_du_cours, fin_du_cours) VALUES (:classe_id, :matiere_id, :prof_id, :debut, :fin)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['classe_id' => $classe_id, 'matiere_id' => $matiere_id, 'prof_id' => $prof_id, 'debut' => $debut, 'fin' => $fin]);
    }

    public function getCours() {
        $sql = "SELECT p.Id, c.Name AS Classe, m.Name AS Matiere, CONCAT(u.Nom, ' ', u.Prenom) AS Professeur, p.debut_du_cours, p.fin_du_cours FROM planning p JOIN classe c ON p.classe_id = c.Id JOIN matiere m ON p.matiere_id = m.Id JOIN users u ON p.prof_id = u.IdUsers ORDER BY p.debut_du_cours DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function supprimerCours($id) {
        $sql = "DELETE FROM planning WHERE Id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
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
                LEFT JOIN signature s ON u.idUsers = s.user_id AND s.planning_id = :planning_id
                WHERE u.role = 'etudiant'
                  AND u.classe_id = (SELECT classe_id FROM planning WHERE Id = :planning_id)
                ORDER BY u.Nom, u.Prenom";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['planning_id' => $planning_id]);
        return $stmt->fetchAll();
    }
}

$gestionCoursAdmin = new GestionCoursAdmin($pdo);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_course'])) {
    if (!empty($_POST['classe_id']) && !empty($_POST['matiere_id']) && !empty($_POST['prof_id']) && !empty($_POST['debut_du_cours']) && !empty($_POST['fin_du_cours'])) {
        $gestionCoursAdmin->creerCours($_POST['classe_id'], $_POST['matiere_id'], $_POST['prof_id'], $_POST['debut_du_cours'], $_POST['fin_du_cours']);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

if (isset($_GET['delete_id'])) {
    $gestionCoursAdmin->supprimerCours($_GET['delete_id']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$cours = $gestionCoursAdmin->getCours();
?>
<?php include "barrenav.php"; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Gestion des Cours</title>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Gestion des Cours</h1>

    <div class="row justify-content-center mb-5">
        <div class="col-lg-6">
            <div class="form-box p-3 border shadow-lg rounded">
                <form action="" method="post">
                    <h2 class="text-center mb-4">Création d'un cours</h2>
                    <div class="mb-3">
                        <label class="form-label">Classe</label>
                        <select class="form-select" name="classe_id" required>
                            <?php foreach ($gestionCoursAdmin->getClasses() as $class) {
                                echo "<option value='{$class['Id']}'>{$class['Name']}</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Matière</label>
                        <select class="form-select" name="matiere_id" required>
                            <?php foreach ($gestionCoursAdmin->getMatieres() as $matiere) {
                                echo "<option value='{$matiere['Id']}'>{$matiere['Name']}</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Professeur</label>
                        <select class="form-select" name="prof_id" required>
                            <?php foreach ($gestionCoursAdmin->getProfesseurs() as $prof) {
                                echo "<option value='{$prof['IdUsers']}'>{$prof['FullName']}</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Début du cours</label>
                        <input type="datetime-local" class="form-control" name="debut_du_cours" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fin du cours</label>
                        <input type="datetime-local" class="form-control" name="fin_du_cours" required>
                    </div>
                    <div class="text-center">
                        <input type="submit" value="Créer" name="create_course" class="btn btn-success">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <h2 class="mb-3">Liste des Cours</h2>
    <div class="table-responsive">
        <table class='table table-bordered table-hover'>
            <thead>
                <tr>
                    <th>Classe</th>
                    <th>Matière</th>
                    <th>Professeur</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Détails</th>
                    <th>Modifier</th>
                    <th>Supprimer</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cours as $course) {
                    echo "<tr>
                        <td>{$course['Classe']}</td>
                        <td>{$course['Matiere']}</td>
                        <td>{$course['Professeur']}</td>
                        <td>{$course['debut_du_cours']}</td>
                        <td>{$course['fin_du_cours']}</td>
                        <td><a href='cours_details.php?id={$course['Id']}' class='btn btn-info btn-sm'><i class='bi bi-eye-fill'></i> Détails</a></td>
                        <td><a href='modifyCours.php?id={$course['Id']}' class='btn btn-warning btn-sm'><i class='bi bi-pencil-fill'></i> Modifier</a></td>
                        <td><a href='?delete_id={$course['Id']}' class='btn btn-danger btn-sm'><i class='bi bi-trash-fill'></i> Supprimer</a></td>
                    </tr>";
                } ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</body>
</html>