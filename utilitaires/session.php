<?php
/**
 * Fonctions utilitaires pour la gestion de session et d'authentification
 * Utiliser : include_once __DIR__ . '/../utilitaires/session.php';
 */

// Démarre la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie que l'utilisateur est connecté
 * Redirige vers la page de connexion si ce n'est pas le cas
 */
function exiger_authentification($page_connexion = 'index.php') {
    if (!isset($_SESSION['email'])) {
        $_SESSION['message_expiration'] = "Votre session a expiré. Veuillez vous reconnecter.";
        header('Location: ' . $page_connexion);
        exit();
    }
}
