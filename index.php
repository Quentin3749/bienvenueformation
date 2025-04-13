<?php
// Connexion à la base de données
include_once "connect_ddb.php";

// Démarrer une session
session_start();

if (isset($_POST['ok'])) {
    $email = trim($_POST['mail']);  // Récupérer l'email depuis le formulaire et sécuriser les entrées
    $password = $_POST['password'];  // Récupérer le mot de passe depuis le formulaire

    // Requête SQL sécurisée avec des paramètres préparés
    $sql = "SELECT * FROM users WHERE mail = :email";  // Utilisation de "mail" comme dans la BDD
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);

    // Vérification si l'utilisateur existe
    if ($stmt->rowCount() > 0) {
        $data = $stmt->fetch(); // Récupérer les données utilisateur

        // Vérification du mot de passe haché
        if (password_verify($password, $data['mp'])) {
            $_SESSION['email'] = $email;  // Stocker l'email dans la session
            $_SESSION['role'] = $data['role']; // Stocker le rôle dans la session
            $_SESSION['prenom'] = $data['prenom']; // Stocker le prénom dans la session

            // Redirection en fonction du rôle
            switch ($data['role']) {
                case 'admin':
                    header('Location: admin/admin.php');
                    break;
                case 'enseignant':
                    header('Location: prof.php');
                    break;
                case 'etudiant':
                    header('Location: eleve.php');
                    break;
                default:
                    echo "Rôle inconnu.";
                    exit();
            }
            exit();
        } else {
            $error = "Mot de passe incorrect.";
        }
    } else {
        $error = "Utilisateur non trouvé.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>Login</title>
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-light bg-dark-subtle">
  <a class="navbar-brand" href="#"><img src="image.png" alt="logo"></a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

 
</nav>
<br/>

<div class="container">
    <h1 class="text-center mt-3 mb-4">Bienvenue Formation</h1>
</div>

<div class="d-flex justify-content-center align-items-center vh-90">
    <div class="form-box p-5 border shadow-lg rounded col-lg-4">
        <form action="" method="post">
        <h1 class="text-center">Connexion</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="inputEmail4" class="form-label">Email</label>
            <input type="email" class="form-control" id="inputEmail4" name="mail" required>
        </div>
        <div class="mb-3">
            <label for="inputPassword4" class="form-label">Mot de passe</label>
            <input type="password" class="form-control" id="inputPassword4" name="password" required>
        </div>

        <div class="text-center">
            <input type="submit" value="Se connecter" name="ok" class="btn btn-primary">
        </div>
        </form>
    </div>
</div>

</body>
</html>
