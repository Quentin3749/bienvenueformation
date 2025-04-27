<?php
/**
 * Classe utilitaire pour la validation centralisée des formulaires
 * Utilisation : include_once __DIR__ . '/validateur.php';
 * Appel : Validateur::email($email), Validateur::motDePasse($mdp), etc.
 */
class Validateur {
    /**
     * Vérifie qu'un email est valide
     */
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Vérifie que le mot de passe a une longueur minimale
     */
    public static function motDePasse($mdp, $longueurMin = 8) {
        return is_string($mdp) && strlen($mdp) >= $longueurMin;
    }

    /**
     * Vérifie que deux mots de passe sont identiques
     */
    public static function confirmationMotDePasse($mdp, $confirmation) {
        return $mdp === $confirmation;
    }

    /**
     * Vérifie qu'un champ texte n'est pas vide
     */
    public static function nonVide($valeur) {
        return isset($valeur) && trim($valeur) !== '';
    }

    /**
     * Vérifie qu'une valeur est un entier positif
     */
    public static function entierPositif($valeur) {
        return filter_var($valeur, FILTER_VALIDATE_INT) !== false && $valeur > 0;
    }

    // Ajoute ici d'autres règles selon tes besoins (ex : format de nom, etc.)
}
