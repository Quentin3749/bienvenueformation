<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
// Inclure le fichier de connexion à la base de données
include_once "connect_ddb.php";

// Classe User pour gérer les utilisateurs
class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function updateRole($id, $role) {
        $validRoles = ['admin', 'enseignant', 'etudiant'];
        if (in_array($role, $validRoles)) {
            $sql = "UPDATE users SET role = :role WHERE IdUsers = :id";
            $stmt = $this->pdo->prepare($sql);
            if ($stmt->execute(['role' => $role, 'id' => $id])) {
                return "Rôle mis à jour avec succès.";
            } else {
                return "Erreur lors de la mise à jour du rôle.";
            }
        } else {
            return "Rôle invalide.";
        }
    }

    public function register($name, $firstname, $mail, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = 'admin';  // rôle par défaut

        $sql = "INSERT INTO users (Nom, Prenom, mail, mp, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute([$name, $firstname, $mail, $hashedPassword, $role])) {
            return "Inscription réussie avec le rôle d'admin.";
        } else {
            return "Erreur lors de l'inscription.";
        }
    }

    public function getAdmins() {
        $sql = "
        SELECT users.IdUsers, users.Nom, users.prenom, users.mail, users.role, classe.Name
        FROM users
        LEFT JOIN classe ON users.classe_id = classe.id
        WHERE users.role = 'admin'";
        return $this->pdo->query($sql);
    }
}

// Initialisation de la classe User
$user = new User($pdo);

$message = "";

// Vérification du changement de rôle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && isset($_POST['role'])) {
    $id = $_POST['id'];
    $role = $_POST['role'];
    $message = $user->updateRole($id, $role);
}

// Vérification du formulaire d'inscription
if (isset($_POST['ok'])) {
    $name = htmlspecialchars($_POST['name']);
    $firstname = htmlspecialchars($_POST['firstname']);
    $mail = htmlspecialchars($_POST['mail']);
    $password = htmlspecialchars($_POST['password']);
    $confirmPassword = htmlspecialchars($_POST['confirmpassword']);

    if ($password === $confirmPassword) {
        $message = $user->register($name, $firstname, $mail, $password);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $message = "Les mots de passe ne correspondent pas.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>Administrateur</title>
</head>
<body>

    <!-- Affichage des messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-info mt-3 mx-auto" style="max-width: 800px;">
            <?= $message ?>
        </div>
    <?php endif; ?>
    <?php 
    include "barrenav.php"; ?>

    <br>
    <div class="d-flex justify-content-center align-items-center vh-90">
        <div class="form-box p-3 border shadow-lg rounded col-lg-3">
            <form action="" method="post">
                <h1 class="text-center mb-4">Inscription administrateur</h1>

                <div class="mb-3">
                    <label for="inputname" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="inputname" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="inputfirstname" class="form-label">Prénom</label>
                    <input type="text" class="form-control" id="inputfirstname" name="firstname" required>
                </div>
                <div class="mb-3">
                    <label for="inputEmail4" class="form-label">Email</label>
                    <input type="email" class="form-control" id="inputEmail4" name="mail" required>
                </div>
                <div class="mb-3">
                    <label for="inputPassword4" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="inputPassword4" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="inputconfirmPassword4" class="form-label">Confirmer le mot de passe</label>
                    <input type="password" class="form-control" id="inputconfirmPassword4" name="confirmpassword" required>
                </div>
                <div class="text-center">
                    <input type="submit" value="Valider" name="ok" class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>
    <br>
    <div class="d-flex justify-content-center">
        <table class="table w-50 table-striped table-dark">
            <thead>
                <tr>
                    <th scope="col">identifiant</th>
                    <th scope="col">nom</th>
                    <th scope="col">prénom</th>
                    <th scope="col">mail</th>
                    <th scope="col">role</th>
                    <th scope="col">modifier</th>
                    <th scope="col">supprimer</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $user->getAdmins();
                if ($result->rowCount() > 0) {
                    while ($row = $result->fetch()) {
                ?>
                <tr>
                    <td><?= $row['IdUsers']?></td>
                    <td><?= $row['Nom']?></td>
                    <td><?= $row['prenom']?></td>
                    <td><?= $row['mail']?></td>
                    <td>
                        <form action="" method="post"> 
                            <input type="hidden" name="id" value="<?= $row['IdUsers'] ?>">
                            <select name="role" class="form-select" onchange="this.form.submit()">
                                <option value="admin" <?= $row['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="enseignant" <?= $row['role'] == 'enseignant' ? 'selected' : '' ?>>Enseignant</option>
                                <option value="etudiant" <?= $row['role'] == 'etudiant' ? 'selected' : '' ?>>Étudiant</option>
                            </select>
                        </form>
                    </td>
                    <td class="image"><a href="modifyUser.php?id=<?= $row['IdUsers'] ?>"> <img src="image/mod.png"alt="modifier"></a></td>
                    <td class="image"><a href="supprimerUser.php?id=<?= $row['IdUsers'] ?>"> <img src="image/sup.png" alt="supprimer"></a></td>
                </tr>
                <?php
                    }
                } else {
                    echo "<p class='message'>0 utilisateur présent !</p>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
