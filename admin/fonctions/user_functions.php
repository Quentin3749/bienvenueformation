<?php
/**
 * Vérifie si l'utilisateur actuel est un administrateur
 *
 * @return bool True si l'utilisateur est admin, sinon false
 */
function isAdmin() {
    // Vérifie si la session contient un utilisateur et si son rôle est 'admin'
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        return false;
    }
    return true;
}

/**
 * Redirige l'utilisateur vers une page donnée avec un message optionnel
 *
 * @param string $page   La page de destination
 * @param string $message Message à afficher après redirection
 * @return void
 */
function redirect($page, $message = '') {
    if (!empty($message)) {
        $_SESSION['message'] = $message;
    }
    header("Location: $page");
    exit;
}

/**
 * Récupère tous les utilisateurs avec leurs informations de classe
 *
 * @param PDO $pdo Instance PDO pour la base de données
 * @return array Tableau d'utilisateurs avec leur classe
 */
function getAllUsers($pdo) {
    $sql = "
        SELECT users.IdUsers, users.Nom, users.prenom, users.mail, users.role, 
               users.classe_id, classe.Name
        FROM users
        LEFT JOIN classe ON users.classe_id = classe.id
        ORDER BY users.Nom, users.prenom
    ";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère toutes les classes disponibles
 *
 * @param PDO $pdo Instance PDO pour la base de données
 * @return array Tableau des classes
 */
function getAllClasses($pdo) {
    $sql = "SELECT id, Name FROM classe ORDER BY Name";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Affiche un message d'alerte
 *
 * @param string $message Le message à afficher
 * @param string $type    Le type d'alerte (success, danger, info, etc.)
 * @return void
 */
function afficherMessage($message, $type = 'info') {
    if (!empty($message)) {
        echo '<div class="alert alert-' . htmlspecialchars($type) . ' text-center">' . htmlspecialchars($message) . '</div>';
    }
}