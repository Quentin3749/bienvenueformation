<?php
// Inclusion des fichiers
require_once "connect_ddb.php";
require_once "fonctions/user_functions.php";
include "barrenav.php"; // Assurez-vous que ce fichier existe

// Classe principale pour la gestion des utilisateurs
class UserManager {
    private $pdo;
    private $message = "";

    // Constructeur declancher automatiquement à chaque creation d'objet
    public function __construct($pdo) {
        $this->pdo = $pdo;
        session_start();
    }

    // recupere un message
    public function getMessage() {
        return $this->message;
    }

    // Traitements des formulaires en appelant trois autres fonctions
    public function processRequests() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $this->processRoleChange();
            $this->processClassChange();
            $this->processRegistration();
        }
    }

    // Traiter le changement de rôle
    private function processRoleChange() {
        if (isset($_POST['id']) && isset($_POST['role'])) {
            $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            $role = htmlspecialchars($_POST['role']);

            // Définir les rôles valides et vérifier que le rôle est autorisé
            $validRoles = ['admin', 'enseignant', 'etudiant'];
            if (in_array($role, $validRoles)) {
                // Préparer et exécuter la requête de mise à jour du rôle
                $sql = "UPDATE users SET role = :role WHERE IdUsers = :id";
                $stmt = $this->pdo->prepare($sql);

                if ($stmt->execute(['role' => $role, 'id' => $id])) {
                    $this->message = "Rôle mis à jour avec succès.";
                } else {
                    $this->message = "Erreur lors de la mise à jour du rôle.";
                }
            } else {
                $this->message = "Rôle invalide.";
            }
        }
    }

    // Traiter le changement de classe
    private function processClassChange() {
        if (isset($_POST['id']) && isset($_POST['classe_id'])) {
            $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            $classe_id = filter_var($_POST['classe_id'], FILTER_SANITIZE_NUMBER_INT);

            $sql = "UPDATE users SET classe_id = :classe_id WHERE IdUsers = :id";
            $stmt = $this->pdo->prepare($sql);

            if ($stmt->execute(['classe_id' => $classe_id ?: null, 'id' => $id])) {
                $this->message = "Classe mise à jour avec succès.";
            } else {
                $this->message = "Erreur lors de la mise à jour de la classe.";
            }
        }
    }

    // Traiter l'inscription
    private function processRegistration() {
        if (isset($_POST['ok'])) {
            $name = htmlspecialchars($_POST['name']);
            $firstname = htmlspecialchars($_POST['firstname']);
            $mail = filter_var($_POST['mail'], FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirmpassword'];

            // Vérification si l'email existe déjà
            $checkEmail = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE mail = ?");
            $checkEmail->execute([$mail]);
            if ($checkEmail->fetchColumn() > 0) {
                $this->message = "Cet email est déjà utilisé.";
            }
            // Vérifier que les mots de passe correspondent okkkkkkkkkkkkkkkkk
            elseif ($password !== $confirmPassword) {
                $this->message = "Les mots de passe ne correspondent pas.";
            }
            // Vérifier la complexité du mot de passe
            elseif (strlen($password) < 8) {
                $this->message = "Le mot de passe doit contenir au moins 8 caractères.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (Nom, Prenom, mail, mp, role) VALUES (?, ?, ?, ?, 'etudiant')";
                $stmt = $this->pdo->prepare($sql);

                if ($stmt->execute([$name, $firstname, $mail, $hashedPassword])) {
                    $this->message = "Utilisateur enregistré avec succès.";
                } else {
                    $this->message = "Erreur lors de l'enregistrement de l'utilisateur.";
                }
            }
        }
    }

    // Récupérer tous les utilisateurs (filtrés pour les étudiants)
    public function getAllUsers() {
        $sql = "
            SELECT users.IdUsers, users.Nom, users.prenom, users.mail, users.role,
                   users.classe_id, classe.Name AS ClasseName
            FROM users
            LEFT JOIN classe ON users.classe_id = classe.id
            WHERE users.role = 'etudiant'
            ORDER BY users.Nom, users.prenom
        ";
        return $this->pdo->query($sql);
    }

    // Récupérer toutes les classes
    public function getAllClasses() {
        $sql = "SELECT id, Name FROM classe ORDER BY Name";
        return $this->pdo->query($sql);
    }
}

// Classe pour le rendu HTML
class UserView {
    private $userManager;

    public function __construct($userManager) {
        $this->userManager = $userManager;
    }

    // Afficher la page complète
    public function render() {
        $this->renderHeader();
        $this->renderNavigation();
        $this->renderMessage();
        $this->renderRegistrationForm(); // Afficher le formulaire en premier
        $this->renderUsersTable();       // Puis le tableau
        $this->renderFooter();
    }

    // Afficher l'en-tête HTML
    private function renderHeader() {
        echo '<!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
            <link rel="stylesheet" href="style.css">
            <title>Gestion des Élèves</title>
        </head>
        <body>';
    }

    // Afficher la barre de navigation
    private function renderNavigation() {
        global $barrenav;
        echo $barrenav;
    }

    // Afficher les messages
    private function renderMessage() {
        $message = $this->userManager->getMessage();
        if (!empty($message)) {
            echo '<div class="alert alert-info text-center mt-3 mx-auto w-50">' . htmlspecialchars($message) . '</div>';
        }
    }

    // Afficher le formulaire d'inscription
    private function renderRegistrationForm() {
        echo '<div class="d-flex justify-content-center align-items-center vh-90 my-4">
            <div class="form-box p-3 border shadow-lg rounded col-lg-3">
                <form action="" method="post">
                    <h1 class="text-center mb-4">Inscription d\'un Élève</h1>

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
                        <small class="form-text text-muted">Le mot de passe doit contenir au moins 8 caractères.</small>
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
        </div>';
    }

    // Afficher le tableau des utilisateurs (élèves)
    private function renderUsersTable() {
        $result = $this->userManager->getAllUsers();

        echo '<div class="d-flex justify-content-center">';
        if ($result && $result->rowCount() > 0) {
            echo '<table class="table w-75 table-striped table-dark">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Nom</th>
                            <th scope="col">Prénom</th>
                            <th scope="col">Email</th>
                            <th scope="col">Rôle</th>
                            <th scope="col">Classe</th>
                            <th scope="col">Modifier</th>
                            <th scope="col">Supprimer</th>
                        </tr>
                    </thead>
                    <tbody>';

            while ($row = $result->fetch()) {
                echo '<tr>
                        <td>' . htmlspecialchars($row['IdUsers']) . '</td>
                        <td>' . htmlspecialchars($row['Nom']) . '</td>
                        <td>' . htmlspecialchars($row['prenom']) . '</td>
                        <td>' . htmlspecialchars($row['mail']) . '</td>
                        <td>
                            <form action="" method="post">
                                <input type="hidden" name="id" value="' . htmlspecialchars($row['IdUsers']) . '">
                                <select name="role" class="form-select" onchange="this.form.submit()">
                                    <option value="etudiant" ' . ($row['role'] == 'etudiant' ? 'selected' : '') . '>Étudiant</option>
                                    <option value="enseignant" ' . ($row['role'] == 'enseignant' ? 'selected' : '') . '>Enseignant</option>
                                    <option value="admin" ' . ($row['role'] == 'admin' ? 'selected' : '') . '>Admin</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form action="" method="post">
                                <input type="hidden" name="id" value="' . htmlspecialchars($row['IdUsers']) . '">
                                <select name="classe_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">-- Aucune classe --</option>';

                                    $classes = $this->userManager->getAllClasses();
                                    while ($class = $classes->fetch()) {
                                        $selected = $row['classe_id'] == $class['id'] ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($class['id']) . '" ' . $selected . '>' .
                                                htmlspecialchars($class['Name']) . '</option>';
                                    }

                            echo '</select>
                            </form>
                        </td>
                        <td class="image">
                            <a href="modifyUser.php?id=' . htmlspecialchars($row['IdUsers']) . '">
                                <img src="image/mod.png" alt="modifier">
                            </a>
                        </td>
                        <td class="image">
                            <a href="supprimerUser.php?id=' . htmlspecialchars($row['IdUsers']) . '">
                                <img src="image/sup.png" alt="supprimer">
                            </a>
                        </td>
                    </tr>';
            }

            echo '</tbody>
                </table>';
        } else {
            echo '<p class="alert alert-info">Aucun élève trouvé</p>';
        }
        echo '</div>';
    }

    // Afficher le pied de page
    private function renderFooter() {
        echo '</body>
        </html>';
    }
}

// Création et exécution du contrôleur principal
class AdminController {
    private $userManager;
    private $userView;

    public function __construct($pdo) {
        $this->userManager = new UserManager($pdo);
        $this->userView = new UserView($this->userManager);
    }

    // Exécuter le contrôleur
    public function run() {
        $this->userManager->processRequests();
        $this->userView->render();
    }
}

// Point d'entrée de l'application
$adminController = new AdminController($pdo);
$adminController->run();
?>