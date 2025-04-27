<?php
include_once __DIR__ . '/configuration/connexion_bdd.php';
include_once __DIR__ . '/utilitaires/session.php';
// Pas de exiger_authentification() ici car c'est la page de connexion

// Classe pour gérer l'authentification des utilisateurs
class Authentification {
    private $pdo; // Stocke la connexion PDO à la base de données

    public function __construct($pdo) {
        $this->pdo = $pdo; // Initialise la connexion PDO
    }

    // Fonction pour connecter un utilisateur avec email et mot de passe
    public function connecterUtilisateur($email, $password) {
        // On nettoie l'email pour éviter les soucis
        $email = trim($email);

        // Prépare la requête pour aller chercher l'utilisateur dans la base de données
        $sql = "SELECT * FROM users WHERE mail = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        // On regarde si on a trouvé un utilisateur avec cet email
        if ($stmt->rowCount() > 0) {
            $data = $stmt->fetch(); // On récupère les informations de l'utilisateur

            // On vérifie si le mot de passe donné correspond au mot de passe enregistré (qui est caché)
            if (password_verify($password, $data['mp'])) {
                // Si tout est bon, on se souvient de l'utilisateur en stockant des infos dans la session
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $data['role'];
                $_SESSION['prenom'] = $data['prenom'];
                $_SESSION['connecte'] = true; // On ajoute une variable pour indiquer que l'utilisateur est connecté

                // On redirige l'utilisateur selon son rôle
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
                // Mot de passe incorrect
                return "Mot de passe incorrect.";
            }
        } else {
            // Utilisateur non trouvé
            return "Utilisateur non trouvé.";
        }
    }
}

// On crée notre "boîte" d'Authentification en lui donnant l'outil pour la base de données ($pdo)
$auth = new Authentification($pdo);

// On regarde si le bouton "Se connecter" a été cliqué
if (isset($_POST['ok'])) {
    // On essaie de connecter l'utilisateur et on récupère le message (s'il y en a un)
    $error = $auth->connecterUtilisateur($_POST['mail'], $_POST['password']);
}

// On vérifie si l'utilisateur était connecté et on supprime l'information de connexion
if (isset($_SESSION['connecte']) && $_SESSION['connecte'] === true) {
    unset($_SESSION['connecte']); // On supprime la variable de connexion
    $message_expiration = "Connexion expirée. Veuillez vous reconnecter.";
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
    <a class="navbar-brand" href="#"><img src="image/logo.png" alt="logo"></a>
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

            <?php if (isset($message_expiration)): ?>
                <div class="alert alert-warning" role="alert">
                    <?php echo $message_expiration; ?>
                </div>
            <?php endif; ?>

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

<script>
    // Empêcher la mise en cache de la page pour forcer l'affichage du message
    window.onload = function() {
        if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
            // Si la page est chargée depuis l'historique (bouton retour), on recharge la page
            window.location.reload();
        }
    };
</script>

</body>
</html>