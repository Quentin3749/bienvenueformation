<?php
include_once __DIR__ . '/../configuration/connexion_bdd.php';
include_once __DIR__ . '/../utilitaires/session.php';
exiger_authentification();

require_once 'connect_ddb.php';
include "barrenav.php";

class GestionCours {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getCoursById($id) {
        $sql = "SELECT Id, classe_id, matiere_id, prof_id, debut_du_cours, fin_du_cours FROM planning WHERE Id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
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

    public function modifierCours($id, $classe_id, $matiere_id, $prof_id, $debut, $fin) {
        $sql = "UPDATE planning SET classe_id = :classe_id, matiere_id = :matiere_id, prof_id = :prof_id, debut_du_cours = :debut, fin_du_cours = :fin WHERE Id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id, 'classe_id' => $classe_id, 'matiere_id' => $matiere_id, 'prof_id' => $prof_id, 'debut' => $debut, 'fin' => $fin]);
    }
}

$gestionCours = new GestionCours($pdo);
$message = "";
$cours = null;

// Récupérer l'ID du cours à modifier
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $coursId = $_GET['id'];
    $cours = $gestionCours->getCoursById($coursId);

    if (!$cours) {
        $message = "<p class='text-danger text-center'>Cours non trouvé.</p>";
    }
} else {
    $message = "<p class='text-danger text-center'>ID de cours invalide.</p>";
}

// Traitement du formulaire de modification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_course'])) {
    if (!empty($_POST['cours_id']) && !empty($_POST['classe_id']) && !empty($_POST['matiere_id']) && !empty($_POST['prof_id']) && !empty($_POST['debut_du_cours']) && !empty($_POST['fin_du_cours'])) {
        $coursIdToUpdate = $_POST['cours_id'];
        if ($gestionCours->modifierCours($_POST['cours_id'], $_POST['classe_id'], $_POST['matiere_id'], $_POST['prof_id'], $_POST['debut_du_cours'], $_POST['fin_du_cours'])) {
            $message = "<p class='text-success text-center'>Cours mis à jour avec succès.</p>";
            // Récupérer les informations mises à jour du cours
            $cours = $gestionCours->getCoursById($coursIdToUpdate);
        } else {
            $message = "<p class='text-danger text-center'>Erreur lors de la mise à jour du cours.</p>";
        }
    } else {
        $message = "<p class='text-danger text-center'>Tous les champs sont requis.</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Modifier le Cours</title>
</head>
<body>
<div class="container mt-5">
    <?= $message ?>
    <?php if ($cours): ?>
        <div class="d-flex justify-content-center align-items-center">
            <div class="form-box p-3 border shadow-lg rounded col-lg-4">
                <h2 class="text-center mb-4">Modifier le cours</h2>
                <form action="" method="post">
                    <input type="hidden" name="cours_id" value="<?= $cours['Id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Classe</label>
                        <select class="form-select" name="classe_id" required>
                            <?php foreach ($gestionCours->getClasses() as $class): ?>
                                <option value="<?= $class['Id'] ?>" <?= ($cours['classe_id'] == $class['Id']) ? 'selected' : '' ?>><?= $class['Name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Matière</label>
                        <select class="form-select" name="matiere_id" required>
                            <?php foreach ($gestionCours->getMatieres() as $matiere): ?>
                                <option value="<?= $matiere['Id'] ?>" <?= ($cours['matiere_id'] == $matiere['Id']) ? 'selected' : '' ?>><?= $matiere['Name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Professeur</label>
                        <select class="form-select" name="prof_id" required>
                            <?php foreach ($gestionCours->getProfesseurs() as $prof): ?>
                                <option value="<?= $prof['IdUsers'] ?>" <?= ($cours['prof_id'] == $prof['IdUsers']) ? 'selected' : '' ?>><?= $prof['FullName'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Début du cours</label>
                        <input type="datetime-local" class="form-control" name="debut_du_cours" value="<?= $cours['debut_du_cours'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fin du cours</label>
                        <input type="datetime-local" class="form-control" name="fin_du_cours" value="<?= $cours['fin_du_cours'] ?>" required>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="update_course" class="btn btn-primary">Modifier le cours</button>
                        <a href="index.php" class="btn btn-secondary ms-2">Retour à la liste</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>