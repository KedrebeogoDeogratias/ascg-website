<?php
/**
 * Configuration de la connexion à la base de données
 * Association Sportive - Projet ASCG
 */

// ============================================
// PARAMÈTRES DE CONNEXION À LA BASE DE DONNÉES
// ============================================

$hote = 'localhost';
$nomBDD = 'association_sportive';
$utilisateur = 'root';
$motDePasse = '';
$charset = 'utf8mb4';

// ============================================
// CONNEXION PDO
// ============================================

$dsn = "mysql:host=$hote;dbname=$nomBDD;charset=$charset";

// Options de configuration PDO
$optionsPDO = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Gestion des erreurs par exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Mode de récupération par défaut
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Désactiver l'émulation des requêtes préparées
];

try {
    // Création de l'instance PDO
    $pdo = new PDO($dsn, $utilisateur, $motDePasse, $optionsPDO);
} catch (PDOException $exception) {
    // En cas d'erreur de connexion
    die('Erreur de connexion à la base de données : ' . $exception->getMessage());
}

// ============================================
// FONCTIONS UTILITAIRES
// ============================================

/**
 * Échappe les caractères spéciaux pour l'affichage HTML
 * 
 * @param string|null $chaine La chaîne à échapper
 * @return string La chaîne échappée
 */
function echapper($chaine) {
    return htmlspecialchars($chaine ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Affiche un message d'alerte Bootstrap
 * 
 * @param string $message Le message à afficher
 * @param string $type Le type d'alerte (success, danger, warning, info)
 * @return string Le code HTML de l'alerte
 */
function afficherAlerte($message, $type = 'info') {
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
         . echapper($message)
         . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>'
         . '</div>';
}
?>
