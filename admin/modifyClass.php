<?php
// Inclusion de la connexion à la base de données
include_once "connect_ddb.php";
include "barrenav.php";

// Classe de gestion des classes (assurez-vous qu'elle est incluse ou redéfinie ici)
class ClasseManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getClassById($id) {
        $sql = "SELECT id, Name FROM classe WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function modifierClasse($id, $nom) {
        $sql = "UPDATE classe SET Name = :name WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['name' => htmlspecialchars($nom), 'id' => $id]);
    }
}

// Création d'une instance de la classe
$classeManager = new ClasseManager($pdo);
$message = "";
$classe = null; // Initialiser $classe à null

// Gestion de la soumission du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateClass'])) {
    if (isset($_POST['classId']) && is_numeric($_POST['classId']) && !empty($_POST['className'])) {
        $classIdToUpdate = $_POST['classId'];
        $newClassName = $_POST['className'];

        if ($classeManager->modifierClasse($classIdToUpdate, $newClassName)) {
            $message = "<p class='text-success text-center'>Modification effectuée avec succès.</p>";
            // Ne plus rediriger immédiatement pour afficher le message
            // header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $classIdToUpdate);
            // exit();
            // Récupérer la classe mise à jour pour afficher les nouvelles valeurs
            $classe = $classeManager->getClassById($classIdToUpdate);
        } else {
            $message = "<p class='text-danger text-center'>Erreur lors de la mise à jour de la classe.</p>";
        }
    } else {
        $message = "<p class='text-danger text-center'>Le nom de la classe est requis.</p>";
    }
}

// Récupérer l'ID de la classe à modifier depuis l'URL AU CHARGEMENT INITIAL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $classId = $_GET['id'];
    $classe = $classeManager->getClassById($classId);

    if (!$classe) {
        $message = "<p class='text-danger text-center'>Classe non trouvée.</p>";
        // Vous pourriez rediriger ici vers une page de liste des classes par exemple
    }
} elseif (empty($message) && $_SERVER['REQUEST_METHOD'] != 'POST') {
    // Afficher le message d'erreur seulement si aucun autre message n'a été défini
    // et si la requête n'est pas une soumission de formulaire
    $message = "<p class='text-danger text-center'>ID de classe invalide.</p>";
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <title>Modifier la Classe</title>
</head>
<body>
<div class="container mt-5">
    <?= $message; ?>
    <?php if (isset($classe)): ?>
        <div class="d-flex justify-content-center">
            <div class="form-box p-4 border shadow-lg rounded col-lg-4">
                <form action="" method="post">
                    <h2 class="text-center mb-3">Modifier la Classe</h2>
                    <input type="hidden" name="classId" value="<?= $classe['id']; ?>">
                    <div class="mb-3">
                        <label for="className" class="form-label">Nom de la Classe</label>
                        <input type="text" class="form-control" id="className" name="className" value="<?= htmlspecialchars($classe['Name']); ?>" required>
                    </div>
                    <div class="text-center">
                        <input type="submit" value="Modifier" name="updateClass" class="btn btn-primary">
                        <a href="liste_classes.php" class="btn btn-secondary ms-2">Retour à la liste</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>