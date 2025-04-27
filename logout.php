<?php
// On démarre la session pour pouvoir la détruire
include_once __DIR__ . '/utilitaires/session.php';
// Détruit la session et redirige
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();
session_destroy();
setcookie(session_name(), '', time()-3600, '/');
header('Location: index.php');
exit();
?>