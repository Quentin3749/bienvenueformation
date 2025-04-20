<?php
session_start();
include_once "connect_ddb.php";

class MatiereManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createMatiere($name) {
        if (!empty($name)) {
            try {
                $sql = "INSERT INTO matiere (Name) VALUES (:subjectName)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['subjectName' => htmlspecialchars($name)]);
                $_SESSION['message'] = "<p class='text-success text-center'>Matière créée avec succès.</p>";
            } catch (PDOException $e) {
                $_SESSION['message'] = "<p class='text-danger text-center'>Erreur lors de la création de la matière.</p>";
            }
        } else {
            $_SESSION['message'] = "<p class='text-danger text-center'>Le nom de la matière est requis.</p>";
        }
        header("Location: adminmatiere.php");
        exit();
    }

    public function getAllMatieres() {
        $sql = "SELECT id, Name FROM matiere";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}

$matiereManager = new MatiereManager($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createSubject'])) {
    $matiereManager->createMatiere($_POST['subjectName']);
}

$matieres = $matiereManager->getAllMatieres();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <title>Gestion des Matières</title>
</head>
<body>
<?php include "barrenav.php"; ?>

<div class="container mt-5">
    <?= $_SESSION['message'] ?? ""; unset($_SESSION['message']); ?>

    <div class="d-flex justify-content-center">
        <div class="form-box p-4 border shadow-lg rounded col-lg-4">
            <form action="" method="post">
                <h2 class="text-center mb-3">Créer une Matière</h2>
                <div class="mb-3">
                    <label for="subjectName" class="form-label">Nom de la Matière</label>
                    <input type="text" class="form-control" id="subjectName" name="subjectName" required>
                </div>
                <div class="text-center">
                    <button type="submit" name="createSubject" class="btn btn-success">Créer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-5">
        <h3 class="text-center">Liste des Matières</h3>
        <table class="table table-striped table-bordered mt-3">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Nom</th>
                    <th scope="col">Modifier</th>
                    <th scope="col">Supprimer</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($matieres)): ?>
                    <?php foreach ($matieres as $matiere): ?>
                        <tr>
                            <td><?= htmlspecialchars($matiere['id']) ?></td>
                            <td><?= htmlspecialchars($matiere['Name']) ?></td>
                            <td><a href="modifier_matiere.php?id=<?= $matiere['id'] ?>"> <img src="image/mod.png" alt="Modifier"></a></td>
                            <td><a href="supprimermatiere.php?delete_id=<?= $matiere['id'] ?>"> <img src="image/sup.png" alt="Supprimer"></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center">Aucune matière trouvée.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
