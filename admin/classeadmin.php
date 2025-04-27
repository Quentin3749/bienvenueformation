<?php
include_once __DIR__ . '/../configuration/connexion_bdd.php';
include_once __DIR__ . '/../utilitaires/session.php';
exiger_authentification();

// Désactive le cache HTTP pour garantir l'actualisation des données
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
// Classe de gestion des classes (ajout, récupération)
class ClasseManager {
    private $pdo;

    // Constructeur : initialise la connexion PDO
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Ajoute une nouvelle classe dans la base de données
    public function ajouterClasse($nom) {
        $sql = "INSERT INTO classe (Name) VALUES (:name)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['name' => htmlspecialchars($nom)]);
    }

    // Récupère la liste de toutes les classes
    public function getClasses() {
        $sql = "SELECT id, Name FROM classe";
        return $this->pdo->query($sql)->fetchAll();
    }
}

// Inclusion de la barre de navigation
include "barrenav.php";

// Création d'une instance du gestionnaire de classes
$classeManager = new ClasseManager($pdo);
$message = "";

// Gestion de l'ajout d'une classe via le formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['createClass'])) {
    if (!empty($_POST['className'])) {
        if ($classeManager->ajouterClasse($_POST['className'])) {
            $message = "<p class='text-success text-center'>Classe créée avec succès.</p>";
        } else {
            $message = "<p class='text-danger text-center'>Erreur lors de la création de la classe.</p>";
        }
    } else {
        $message = "<p class='text-danger text-center'>Le nom de la classe est requis.</p>";
    }
    // Redirige pour éviter la double soumission du formulaire
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Récupère la liste des classes pour affichage
$classes = $classeManager->getClasses();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <title>Gestion des Classes</title>
</head>
<body>
<!-- Affichage du message de confirmation ou d'erreur -->
<div class="container mt-5">
    <?= $message; ?>
    <!-- Formulaire pour créer une nouvelle classe -->
    <div class="d-flex justify-content-center">
        <div class="form-box p-4 border shadow-lg rounded col-lg-4">
            <form action="" method="post">
                <h2 class="text-center mb-3">Créer une Classe</h2>
                <div class="mb-3">
                    <label for="className" class="form-label">Nom de la Classe</label>
                    <input type="text" class="form-control" id="className" name="className" required>
                </div>
                <div class="text-center">
                    <input type="submit" value="Créer" name="createClass" class="btn btn-success">
                </div>
            </form>
        </div>
    </div>
    <!-- Affichage de la liste des classes -->
    <div class="mt-5">
        <h3 class="text-center">Liste des Classes</h3>
        <table class="table table-striped table-bordered mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Modifier</th>
                    <th>Supprimer</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($classes): ?>
                    <?php foreach ($classes as $row): ?>
                        <tr>
                            <td><?= $row['id']; ?></td>
                            <td><?= $row['Name']; ?></td>
                            <td class="image"><a href="modifyClass.php?id=<?= $row['id']; ?>"> <img src="image/mod.png" alt="modifier"></a></td>
                            <td class="image"><a href="supprimerclasse.php?delete_id=<?= $row['id']; ?>"> <img src="image/sup.png" alt="supprimer"></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center">Aucune classe trouvée.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
