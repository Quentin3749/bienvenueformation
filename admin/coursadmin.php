<?php
require_once 'connect_ddb.php';


class GestionCours {
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
        $sql = "SELECT p.Id, c.Name AS Classe, m.Name AS Matiere, CONCAT(u.Nom, ' ', u.Prenom) AS Professeur, p.debut_du_cours, p.fin_du_cours FROM planning p JOIN classe c ON p.classe_id = c.Id JOIN matiere m ON p.matiere_id = m.Id JOIN users u ON p.prof_id = u.IdUsers";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function supprimerCours($id) {
        $sql = "DELETE FROM planning WHERE Id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}

$gestionCours = new GestionCours($pdo);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_course'])) {
    if (!empty($_POST['classe_id']) && !empty($_POST['matiere_id']) && !empty($_POST['prof_id']) && !empty($_POST['debut_du_cours']) && !empty($_POST['fin_du_cours'])) {
        $gestionCours->creerCours($_POST['classe_id'], $_POST['matiere_id'], $_POST['prof_id'], $_POST['debut_du_cours'], $_POST['fin_du_cours']);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

if (isset($_GET['delete_id'])) {
    $gestionCours->supprimerCours($_GET['delete_id']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
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
<div class="d-flex justify-content-center align-items-center vh-90">
    <div class="form-box p-3 border shadow-lg rounded col-lg-3">
        <form action="" method="post">
            <h2 class="text-center mb-4">Création d'un cours</h2>
            <div class="mb-3">
                <label class="form-label">Classe</label>
                <select class="form-select" name="classe_id" required>
                    <?php foreach ($gestionCours->getClasses() as $class) {
                        echo "<option value='{$class['Id']}'>{$class['Name']}</option>";
                    } ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Matière</label>
                <select class="form-select" name="matiere_id" required>
                    <?php foreach ($gestionCours->getMatieres() as $matiere) {
                        echo "<option value='{$matiere['Id']}'>{$matiere['Name']}</option>";
                    } ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Professeur</label>
                <select class="form-select" name="prof_id" required>
                    <?php foreach ($gestionCours->getProfesseurs() as $prof) {
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

<div class='d-flex justify-content-center'>
    <table class='table w-75'>
        <thead>
            <tr>
                <th>Classe</th>
                <th>Matière</th>
                <th>Professeur</th>
                <th>Début</th>
                <th>Fin</th>
                <th>Modifier</th>
                <th>Supprimer</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($gestionCours->getCours() as $course) {
                echo "<tr>
                    <td>{$course['Classe']}</td>
                    <td>{$course['Matiere']}</td>
                    <td>{$course['Professeur']}</td>
                    <td>{$course['debut_du_cours']}</td>
                    <td>{$course['fin_du_cours']}</td>
                    <td><a href='modifyCourse.php?id={$course['Id']}' class='btn btn-warning'>Modifier</a></td>
                    <td><a href='?delete_id={$course['Id']}' class='btn btn-danger'>Supprimer</a></td>
                </tr>";
            } ?>
        </tbody>
    </table>
</div>
</body>
</html>
