<?php
include_once __DIR__ . '/../configuration/connexion_bdd.php';
include_once __DIR__ . '/../utilitaires/session.php';
exiger_authentification();

// Désactive le cache HTTP pour garantir l'actualisation des données
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Inclusion des fichiers nécessaires (fonctions utilisateurs)
require_once "fonctions/user_functions.php";

// Classe principale pour la gestion des utilisateurs (CRUD, rôles, classes, etc.)
class UserManager {
    private $pdo;
    private $message = "";
    
    // Constructeur appelé à chaque création d'objet UserManager
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Récupère le message d'information ou d'erreur
    public function getMessage() {
        return $this->message;
    }
    
    // Traite les formulaires POST (rôle, classe, inscription)
    public function processRequests() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $this->processRoleChange();
            $this->processClassChange();
            $this->processRegistration();
        }
    }
    
    // Traite le changement de rôle d'un utilisateur
    private function processRoleChange() {
        if (isset($_POST['id']) && isset($_POST['role'])) {
            $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
            $role = htmlspecialchars($_POST['role']);
            
            // Rôles autorisés
            $validRoles = ['admin', 'enseignant', 'etudiant'];
            if (in_array($role, $validRoles)) {
                // Met à jour le rôle dans la BDD
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
    
    // Traite le changement de classe d'un utilisateur
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
    
    // Traite l'inscription d'un nouvel utilisateur
    private function processRegistration() {
        if (isset($_POST['ok'])) {
            $name = htmlspecialchars($_POST['name']);
            $firstname = htmlspecialchars($_POST['firstname']);
            $mail = filter_var($_POST['mail'], FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirmpassword'];
            
            // Vérifie si l'email existe déjà
            $checkEmail = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE mail = ?");
            $checkEmail->execute([$mail]);
            if ($checkEmail->fetchColumn() > 0) {
                $this->message = "Cet email est déjà utilisé.";
            }
            // Vérifie que les mots de passe correspondent
            elseif ($password !== $confirmPassword) {
                $this->message = "Les mots de passe ne correspondent pas.";
            }
            // Vérifie la complexité du mot de passe
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
    
    // Récupère tous les utilisateurs avec leur classe (jointure)
    public function getAllUsers() {
        $sql = "
        SELECT users.IdUsers, users.Nom, users.prenom, users.mail, users.role, 
               users.classe_id, classe.Name
        FROM users
        LEFT JOIN classe ON users.classe_id = classe.id
        ORDER BY users.Nom, users.prenom
        ";
        return $this->pdo->query($sql);
    }
    
    // Récupère toutes les classes disponibles
    public function getAllClasses() {
        $sql = "SELECT id, Name FROM classe ORDER BY Name";
        return $this->pdo->query($sql);
    }
}

// Classe pour le rendu HTML de la page admin (header, navigation, messages, formulaires, tableau)
class UserView {
    private $userManager;
    
    public function __construct($userManager) {
        $this->userManager = $userManager;
    }
    
    // Affiche la page complète
    public function render() {
        $this->renderHeader();
        $this->renderNavigation();
        $this->renderMessage();
        $this->renderRegistrationForm();
        $this->renderUsersTable();
        $this->renderFooter();
    }
    
    // Affiche l'en-tête HTML
    private function renderHeader() {
        echo '<!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
            <link rel="stylesheet" href="style.css">
            <title>Administrateur</title>
        </head>
        <body>';
    }
    
    // Affiche la barre de navigation
    private function renderNavigation() {
        include "barrenav.php";
    }
    
    // Affiche les messages d'info ou d'erreur
    private function renderMessage() {
        $message = $this->userManager->getMessage();
        if (!empty($message)) {
            echo '<div class="alert alert-info text-center mt-3 mx-auto w-50">' . htmlspecialchars($message) . '</div>';
        }
    }
    
    // Affiche le formulaire d'inscription
    private function renderRegistrationForm() {
        echo '<div class="d-flex justify-content-center align-items-center vh-90 my-4">
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
    
    // Affiche le tableau des utilisateurs
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
                                <option value="admin" ' . ($row['role'] == 'admin' ? 'selected' : '') . '>Admin</option>
                                <option value="enseignant" ' . ($row['role'] == 'enseignant' ? 'selected' : '') . '>Enseignant</option>
                                <option value="etudiant" ' . ($row['role'] == 'etudiant' ? 'selected' : '') . '>Étudiant</option>
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
            echo '<p class="alert alert-info">Aucun utilisateur trouvé</p>';
        }
        echo '</div>';
    }
    
    // Affiche le pied de page
    private function renderFooter() {
        echo '</body>
        </html>';
    }
}

// Contrôleur principal pour orchestrer la gestion admin
class AdminController {
    private $userManager;
    private $userView;
    
    public function __construct($pdo) {
        $this->userManager = new UserManager($pdo);
        $this->userManager->processRequests();
        $this->userView = new UserView($this->userManager);
    }
    
    // Lance le rendu de la page admin
    public function run() {
        $this->userView->render();
    }
}

// Point d'entrée de l'application admin
$adminController = new AdminController($pdo);
$adminController->run();
?>