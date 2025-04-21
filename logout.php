<?php
// On démarre la session pour pouvoir la détruire
session_start();

// On vide les variables de session
$_SESSION = array();

// Si des cookies de session sont utilisés, on les détruit
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// On détruit la session
session_destroy();

// On redirige vers la page de connexion avec un message
header('Location: index.php?logout=success');
exit();
?>