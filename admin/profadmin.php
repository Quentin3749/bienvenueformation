<?php
include_once "connect_ddb.php";

// Classe principale pour gérer les utilisateurs
class UserManagement {
    private $pdo;

    // Constructeur pour initialiser la connexion à la base de données
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Fonction pour afficher les messages d'erreur ou de succès
    public function displayMessage($message, $type = 'success') {
        echo "<div class='alert alert-$type' role='alert'>$message</div>";
    }

    // Fonction pour mettre à jour le rôle d'un utilisateur
    public function updateUserRole($id, $role) {
        $validRoles = ['admin', 'enseignant', 'etudiant'];

        if (in_array($role, $validRoles)) {
            $sql = "UPDATE users SET role = :role WHERE IdUsers = :id";
            $stmt = $this->pdo->prepare($sql);

            if ($stmt->execute(['role' => $role, 'id' => $id])) {
                $this->displayMessage("Rôle mis à jour avec succès.");
            } else {
                $this->displayMessage("Erreur lors de la mise à jour du rôle.", 'danger');
            }
        } else {
            $this->displayMessage("Rôle invalide.", 'danger');
        }
    }

    // Fonction pour inscrire un utilisateur
    public function registerUser($name, $firstname, $mail, $password, $confirmPassword) {
        if ($password === $confirmPassword) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = 'enseignant'; // Rôle par défaut

            $sql = "INSERT INTO users (Nom, Prenom, mail, mp, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);

            if ($stmt->execute([$name, $firstname, $mail, $hashedPassword, $role])) {
                $this->displayMessage("Inscription réussie avec le rôle d'enseignant.");
            } else {
                $this->displayMessage("Erreur lors de l'inscription.", 'danger');
            }
        } else {
            $this->displayMessage("Les mots de passe ne correspondent pas.", 'danger');
        }
    }

    // Fonction pour récupérer les utilisateurs
    public function getUsers() {
        $sql = "
            SELECT users.IdUsers, users.Nom, users.prenom, users.mail, users.role, classe.Name
            FROM users
            LEFT JOIN classe ON users.classe_id = classe.id
            WHERE users.role = 'enseignant'"; // Filtrer uniquement les enseignants
        return $this->pdo->query($sql);
    }
}

// Initialisation de l'objet UserManagement
$userManagement = new UserManagement($pdo);

// Traitement du formulaire de changement de rôle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'], $_POST['role'])) {
    $id = $_POST['id'];
    $role = $_POST['role'];
    $userManagement->updateUserRole($id, $role);
}

// Traitement du formulaire d'inscription
if (isset($_POST['ok'])) {
    $name = htmlspecialchars($_POST['name']);
    $firstname = htmlspecialchars($_POST['firstname']);
    $mail = htmlspecialchars($_POST['mail']);
    $password = htmlspecialchars($_POST['password']);
    $confirmPassword = htmlspecialchars($_POST['confirmpassword']);

    $userManagement->registerUser($name, $firstname, $mail, $password, $confirmPassword);

    // Redirection pour éviter les soumissions multiples
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
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
    <?php include "barrenav.php"; ?>

    <br>
    <div class="d-flex justify-content-center align-items-center vh90">
        <div class="form-box p-3 border shadow-lg rounded col-lg-3">
            <form action="" method="post">
                <h1 class="text-center mb-4">Inscription</h1>

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
                    <th scope="col">Identifiant</th>
                    <th scope="col">Nom</th>
                    <th scope="col">Prénom</th>
                    <th scope="col">Email</th>
                    <th scope="col">Rôle</th>
                    <th scope="col">Modifier</th>
                    <th scope="col">Supprimer</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $userManagement->getUsers();

                if ($result->rowCount() > 0) {
                    while ($row = $result->fetch()) {
                ?>
                    <tr>
                        <td><?= $row['IdUsers'] ?></td>
                        <td><?= $row['Nom'] ?></td>
                        <td><?= $row['prenom'] ?></td>
                        <td><?= $row['mail'] ?></td>
                        <td>
                            <!-- Formulaire pour changer le rôle -->
                            <form action="" method="post"> 
                                <input type="hidden" name="id" value="<?= $row['IdUsers'] ?>">
                                <select name="role" class="form-select" onchange="this.form.submit()">
                                    <option value="admin" <?= $row['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="enseignant" <?= $row['role'] == 'enseignant' ? 'selected' : '' ?>>Enseignant</option>
                                    <option value="etudiant" <?= $row['role'] == 'etudiant' ? 'selected' : '' ?>>Étudiant</option>
                                </select>
                            </form>
                        </td>
                        <td class="image"><a href="modifyUser.php?id=<?= $row['IdUsers'] ?>"> <img src="image/mod.png" alt="modifier"></a></td>
                        <td class="image"><a href="supprimerUser.php?id=<?= $row['IdUsers'] ?>"> <img src="image/sup.png" alt="supprimer"></a></td>
                    </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>Aucun utilisateur trouvé.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
