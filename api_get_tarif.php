<?php
/**
 * API - Calcul du tarif avec remise familiale
 * Association Sportive - Projet ASCG
 * 
 * Paramètres GET :
 *   - id_adherent : Numéro de l'adhérent
 *   - id_activite : Numéro de l'activité
 * 
 * Retourne JSON :
 *   - montant : Montant final à payer
 *   - montantOriginal : Tarif sans remise
 *   - remise : true/false si remise applicable
 *   - pourcentageRemise : Pourcentage de la remise (10)
 *   - parentNom : Nom du parent qui donne droit à la remise
 *   - parentPrenom : Prénom du parent
 *   - natureRelation : Nature du lien de parenté
 *   - libelleActivite : Nom de l'activité
 */

// Headers pour JSON et CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Inclusion de la configuration
require_once 'config.php';

// ============================================
// RÉCUPÉRATION DES PARAMÈTRES
// ============================================

$idAdherent = isset($_GET['id_adherent']) ? (int) $_GET['id_adherent'] : 0;
$idActivite = isset($_GET['id_activite']) ? (int) $_GET['id_activite'] : 0;

// Vérification des paramètres
if ($idAdherent <= 0 || $idActivite <= 0) {
    echo json_encode([
        'erreur' => true,
        'message' => 'Paramètres invalides',
        'montant' => 0,
        'remise' => false
    ]);
    exit;
}

// ============================================
// RÉPONSE PAR DÉFAUT
// ============================================

$reponse = [
    'erreur' => false,
    'montant' => 0,
    'montantOriginal' => 0,
    'remise' => false,
    'pourcentageRemise' => 10,
    'economie' => 0,
    'parentNom' => '',
    'parentPrenom' => '',
    'natureRelation' => '',
    'libelleActivite' => ''
];

try {
    // ============================================
    // RÉCUPÉRER LE TARIF DE L'ACTIVITÉ
    // ============================================
    
    $requeteTarif = $pdo->prepare('
        SELECT Tarif_Annuel, Libelle 
        FROM ACTIVITE 
        WHERE Num_Activite = :idActivite
    ');
    $requeteTarif->execute(['idActivite' => $idActivite]);
    $activite = $requeteTarif->fetch();
    
    if (!$activite) {
        echo json_encode([
            'erreur' => true,
            'message' => 'Activité non trouvée',
            'montant' => 0,
            'remise' => false
        ]);
        exit;
    }
    
    $tarifOriginal = (float) $activite['Tarif_Annuel'];
    $reponse['montantOriginal'] = $tarifOriginal;
    $reponse['montant'] = $tarifOriginal;
    $reponse['libelleActivite'] = $activite['Libelle'];
    
    // ============================================
    // RECHERCHER LES MEMBRES DE LA FAMILLE
    // (dans les deux sens de la relation)
    // ============================================
    
    $requeteParente = $pdo->prepare('
        SELECT a.Num_Adherent AS Num_Parent, a.Nom, a.Prenom, p.Nature
        FROM PARENTE p
        INNER JOIN ADHERENT a ON a.Num_Adherent = p.Num_Adherent_2
        WHERE p.Num_Adherent_1 = :numAdherent1
        
        UNION
        
        SELECT a.Num_Adherent AS Num_Parent, a.Nom, a.Prenom, p.Nature
        FROM PARENTE p
        INNER JOIN ADHERENT a ON a.Num_Adherent = p.Num_Adherent_1
        WHERE p.Num_Adherent_2 = :numAdherent2
    ');
    $requeteParente->execute([
        'numAdherent1' => $idAdherent,
        'numAdherent2' => $idAdherent
    ]);
    $membresParente = $requeteParente->fetchAll();
    
    // ============================================
    // VÉRIFIER SI UN MEMBRE EST INSCRIT À LA MÊME ACTIVITÉ
    // ============================================
    
    foreach ($membresParente as $parent) {
        $requeteInscription = $pdo->prepare('
            SELECT COUNT(*) as inscrit 
            FROM ADHESION 
            WHERE Num_Adherent = :numParent AND Num_Activite = :numActivite
        ');
        $requeteInscription->execute([
            'numParent' => $parent['Num_Parent'],
            'numActivite' => $idActivite
        ]);
        $resultatInscription = $requeteInscription->fetch();
        
        if ($resultatInscription['inscrit'] > 0) {
            // Un membre de la famille est inscrit à la même activité !
            $reponse['remise'] = true;
            $reponse['montant'] = round($tarifOriginal * 0.9, 2);
            $reponse['economie'] = round($tarifOriginal * 0.1, 2);
            $reponse['parentNom'] = $parent['Nom'];
            $reponse['parentPrenom'] = $parent['Prenom'];
            $reponse['natureRelation'] = $parent['Nature'];
            break; // On s'arrête au premier parent trouvé
        }
    }
    
} catch (PDOException $exception) {
    $reponse = [
        'erreur' => true,
        'message' => 'Erreur de base de données : ' . $exception->getMessage(),
        'montant' => 0,
        'remise' => false
    ];
}

// ============================================
// ENVOI DE LA RÉPONSE JSON
// ============================================

echo json_encode($reponse, JSON_UNESCAPED_UNICODE);
?>
