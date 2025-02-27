<?php
/**
 * Vérifie si l'utilisateur actuel est un administrateur
 * 
 * @return bool
 */
function isAdmin() {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        return false;
    }
    return true;
}

/**
 * Redirige l'utilisateur avec un message
 * 
 * @param string $page
 * @param string $message
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
 * @param PDO $pdo
 * @return array
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
 * Récupère toutes les classes
 * 
 * @param PDO $pdo
 * @return array
 */
function getAllClasses($pdo) {
    $sql = "SELECT id, Name FROM classe ORDER BY Name";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Affiche un message d'alerte
 * 
 * @param string $message
 * @param string $type (info, success, warning, danger)
 * @return void
 */
function afficherMessage($message, $type = 'info') {
    if (!empty($message)) {
        echo '<div class="alert alert-' . $type . ' text-center mt-3 mx-auto w-50">' . 
             htmlspecialchars($message) . '</div>';
    }
}