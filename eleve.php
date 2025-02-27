<?php
session_start();
include_once "connect_ddb.php";

// Récupérer les informations de l'élève
$prenom = "Utilisateur inconnu";
$userId = null;
$classeId = null;

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    try {
        $sql = "SELECT IdUsers, prenom, classe_id FROM users WHERE mail = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        
        if ($stmt->rowCount() > 0) {
            $data = $stmt->fetch();
            $prenom = $data['prenom'];
            $userId = $data['IdUsers'];
            $classeId = $data['classe_id'];
        }
    } catch (PDOException $e) {
        // Optionnel: ajouter un log d'erreur pour la base de données
        $errorMessage = "Erreur lors de la récupération des informations de l'élève.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Élève</title>
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
    <?php if (isset($userId) && isset($classeId)): ?>
        <h2 class="text-center mb-4">Mes Cours</h2>
        <?php
        try {
            $sqlCours = "
                SELECT 
                    c.Name AS nom_classe,
                    m.Name AS nom_matiere,
                    CONCAT(u.Nom, ' ', u.Prenom) AS nom_prof,
                    DATE_FORMAT(p.debut_du_cours, '%d/%m/%Y %H:%i') as debut,
                    DATE_FORMAT(p.fin_du_cours, '%d/%m/%Y %H:%i') as fin
                FROM planning p
                JOIN classe c ON p.classe_id = c.Id
                JOIN matiere m ON p.matiere_id = m.Id
                JOIN users u ON p.prof_id = u.IdUsers
                WHERE p.classe_id = :classe_id
                ORDER BY p.debut_du_cours ASC
            ";
            
            $stmtCours = $pdo->prepare($sqlCours);
            $stmtCours->execute(['classe_id' => $classeId]);
            
            if ($stmtCours->rowCount() > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Classe</th>
                                <th>Matière</th>
                                <th>Professeur</th>
                                <th>Début</th>
                                <th>Fin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $stmtCours->fetch()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nom_classe']) ?></td>
                                    <td><?= htmlspecialchars($row['nom_matiere']) ?></td>
                                    <td><?= htmlspecialchars($row['nom_prof']) ?></td>
                                    <td><?= htmlspecialchars($row['debut']) ?></td>
                                    <td><?= htmlspecialchars($row['fin']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class='alert alert-info'>Aucun cours n'est programmé pour votre classe.</div>
            <?php endif; ?>
        <?php
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Une erreur est survenue lors de la récupération des cours.</div>";
        }
        ?>
    <?php else: ?>
        <div class="alert alert-success">
           vous etes connecté
        </div>
    <?php endif; ?>
</div>

</body>
</html>
