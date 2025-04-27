<?php
include_once __DIR__ . '/../configuration/connexion_bdd.php';
include_once __DIR__ . '/../utilitaires/session.php';
exiger_authentification();

// Inclusion des fichiers
require_once "connect_ddb.php";
require_once "fonctions/user_functions.php";

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Initialisation des variables
$message = "";
$userData = null;

// Récupérer l'ID de l'utilisateur à modifier
if (isset($_GET['id'])) {
    $userId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Récupérer les données de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE IdUsers = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        $message = "Utilisateur non trouvé.";
    }
}

// Traitement du formulaire de modification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $userId = filter_var($_POST['userId'], FILTER_SANITIZE_NUMBER_INT);
    $nom = htmlspecialchars($_POST['name']);
    $prenom = htmlspecialchars($_POST['firstname']);
    $email = filter_var($_POST['mail'], FILTER_SANITIZE_EMAIL);
    $role = htmlspecialchars($_POST['role']);
    $classeId = filter_var($_POST['classe_id'], FILTER_SANITIZE_NUMBER_INT) ?: null;
    
    // Vérifier si l'email existe déjà pour un autre utilisateur
    $checkEmail = $pdo->prepare("SELECT COUNT(*) FROM users WHERE mail = ? AND IdUsers != ?");
    $checkEmail->execute([$email, $userId]);
    
    if ($checkEmail->fetchColumn() > 0) {
        $message = "Cet email est déjà utilisé par un autre utilisateur.";
    } else {
        // Vérifier si le mot de passe doit être mis à jour
        if (!empty($_POST['password']) && !empty($_POST['confirmpassword'])) {
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirmpassword'];
            
            if ($password !== $confirmPassword) {
                $message = "Les mots de passe ne correspondent pas.";
            } elseif (strlen($password) < 8) {
                $message = "Le mot de passe doit contenir au moins 8 caractères.";
            } else {
                // Mise à jour avec nouveau mot de passe
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET Nom = ?, Prenom = ?, mail = ?, mp = ?, role = ?, classe_id = ? WHERE IdUsers = ?";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([$nom, $prenom, $email, $hashedPassword, $role, $classeId, $userId])) {
                    $message = "Utilisateur mis à jour avec succès.";
                    // Récupérer les données mises à jour
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE IdUsers = ?");
                    $stmt->execute([$userId]);
                    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $message = "Erreur lors de la mise à jour de l'utilisateur.";
                }
            }
        } else {
            // Mise à jour sans changer le mot de passe
            $sql = "UPDATE users SET Nom = ?, Prenom = ?, mail = ?, role = ?, classe_id = ? WHERE IdUsers = ?";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$nom, $prenom, $email, $role, $classeId, $userId])) {
                $message = "Utilisateur mis à jour avec succès.";
                // Récupérer les données mises à jour
                $stmt = $pdo->prepare("SELECT * FROM users WHERE IdUsers = ?");
                $stmt->execute([$userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $message = "Erreur lors de la mise à jour de l'utilisateur.";
            }
        }
    }
}

// Récupérer toutes les classes pour le formulaire
$classes = $pdo->query("SELECT id, Name FROM classe ORDER BY Name");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>Modifier un utilisateur</title>
</head>
<body>
    <?php include "barrenav.php"; ?>
    
    <div class="container mt-4">
        <h1 class="text-center">Modifier un utilisateur</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-info text-center mt-3 mx-auto w-50"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($userData): ?>
            <div class="d-flex justify-content-center align-items-center vh-90 my-4">
                <div class="form-box p-4 border shadow-lg rounded col-lg-6">
                    <form action="" method="post">
                        <input type="hidden" name="userId" value="<?php echo htmlspecialchars($userData['IdUsers']); ?>">
                        
                        <div class="mb-3">
                            <label for="inputname" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="inputname" name="name" value="<?php echo htmlspecialchars($userData['Nom']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="inputfirstname" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="inputfirstname" name="firstname" value="<?php echo htmlspecialchars($userData['prenom']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="inputEmail4" class="form-label">Email</label>
                            <input type="email" class="form-control" id="inputEmail4" name="mail" value="<?php echo htmlspecialchars($userData['mail']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="inputRole" class="form-label">Rôle</label>
                            <select name="role" id="inputRole" class="form-select">
                                <option value="admin" <?php echo ($userData['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="enseignant" <?php echo ($userData['role'] == 'enseignant') ? 'selected' : ''; ?>>Enseignant</option>
                                <option value="etudiant" <?php echo ($userData['role'] == 'etudiant') ? 'selected' : ''; ?>>Étudiant</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="inputClasse" class="form-label">Classe</label>
                            <select name="classe_id" id="inputClasse" class="form-select">
                                <option value="">-- Aucune classe --</option>
                                <?php while ($class = $classes->fetch()): ?>
                                    <option value="<?php echo htmlspecialchars($class['id']); ?>" 
                                        <?php echo ($userData['classe_id'] == $class['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['Name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="inputPassword4" class="form-label">Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
                            <input type="password" class="form-control" id="inputPassword4" name="password">
                            <small class="form-text text-muted">Le mot de passe doit contenir au moins 8 caractères.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="inputconfirmPassword4" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="inputconfirmPassword4" name="confirmpassword">
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="admin.php" class="btn btn-secondary">Retour</a>
                            <input type="submit" value="Mettre à jour" name="update" class="btn btn-primary">
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger text-center mt-3 mx-auto w-50">
                Utilisateur non trouvé. <a href="admin.php" class="btn btn-secondary btn-sm ms-3">Retour</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>