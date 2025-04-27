<?php
include_once __DIR__ . '/../configuration/connexion_bdd.php';
include_once __DIR__ . '/../utilitaires/session.php';
exiger_authentification();

// session_start(); // Supprimé car déjà géré de façon centralisée
include_once "connect_ddb.php";
include "barrenav.php"; // Assurez-vous que ce fichier existe

class MatiereManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getMatiereById($id) {
        $sql = "SELECT id, Name FROM matiere WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function modifierMatiere($id, $nom) {
        $sql = "UPDATE matiere SET Name = :name WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['name' => htmlspecialchars($nom), 'id' => $id]);
    }
}

$matiereManager = new MatiereManager($pdo);
$message = "";
$matiere = null;

// Gestion de la soumission du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateMatiere'])) {
    if (isset($_POST['matiereId']) && is_numeric($_POST['matiereId']) && !empty($_POST['matiereName'])) {
        $matiereIdToUpdate = $_POST['matiereId'];
        $newMatiereName = $_POST['matiereName'];

        if ($matiereManager->modifierMatiere($matiereIdToUpdate, $newMatiereName)) {
            $message = "<p class='text-success text-center'>Matière mise à jour avec succès.</p>";
            // Récupérer la matière mise à jour pour afficher les nouvelles valeurs
            $matiere = $matiereManager->getMatiereById($matiereIdToUpdate);
        } else {
            $message = "<p class='text-danger text-center'>Erreur lors de la mise à jour de la matière.</p>";
        }
    } else {
        $message = "<p class='text-danger text-center'>Le nom de la matière est requis.</p>";
    }
}

// Récupérer l'ID de la matière à modifier depuis l'URL AU CHARGEMENT INITIAL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $matiereId = $_GET['id'];
    $matiere = $matiereManager->getMatiereById($matiereId);

    if (!$matiere) {
        $message = "<p class='text-danger text-center'>Matière non trouvée.</p>";
        // Vous pourriez rediriger ici vers la liste des matières
    }
} elseif (empty($message) && $_SERVER['REQUEST_METHOD'] != 'POST') {
    $message = "<p class='text-danger text-center'>ID de matière invalide.</p>";
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <title>Modifier la Matière</title>
</head>
<body>
<div class="container mt-5">
    <?= $message; ?>
    <?php if (isset($matiere)): ?>
        <div class="d-flex justify-content-center">
            <div class="form-box p-4 border shadow-lg rounded col-lg-4">
                <form action="" method="post">
                    <h2 class="text-center mb-3">Modifier la Matière</h2>
                    <input type="hidden" name="matiereId" value="<?= $matiere['id']; ?>">
                    <div class="mb-3">
                        <label for="matiereName" class="form-label">Nom de la Matière</label>
                        <input type="text" class="form-control" id="matiereName" name="matiereName" value="<?= htmlspecialchars($matiere['Name']); ?>" required>
                    </div>
                    <div class="text-center">
                        <input type="submit" value="Modifier" name="updateMatiere" class="btn btn-primary">
                        <a href="adminmatiere.php" class="btn btn-secondary ms-2">Retour à la liste</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>