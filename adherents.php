<?php
/**
 * Page de liste des adhérents
 * Association Sportive - Projet ASCG
 */

// Titre de la page
$titrePage = 'Liste des adhérents';

// Inclusion de la configuration
require_once 'config.php';

// ============================================
// TRAITEMENT DE LA SUPPRESSION (si demandée)
// ============================================

$messageAlerte = '';

if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
    $numeroAdherent = (int) $_GET['supprimer'];
    
    try {
        // Requête de suppression
        $requete = $pdo->prepare('DELETE FROM ADHERENT WHERE Num_Adherent = :numero');
        $requete->execute(['numero' => $numeroAdherent]);
        
        if ($requete->rowCount() > 0) {
            $messageAlerte = afficherAlerte('Adhérent supprimé avec succès.', 'success');
        } else {
            $messageAlerte = afficherAlerte('Adhérent non trouvé.', 'warning');
        }
    } catch (PDOException $exception) {
        $messageAlerte = afficherAlerte('Erreur lors de la suppression : ' . $exception->getMessage(), 'danger');
    }
}

// ============================================
// RÉCUPÉRATION DES ADHÉRENTS
// ============================================

// Gestion de la recherche
$recherche = isset($_GET['recherche']) ? trim($_GET['recherche']) : '';

try {
    if (!empty($recherche)) {
        // Requête avec recherche
        $termeRecherche = '%' . $recherche . '%';
        $requete = $pdo->prepare('
            SELECT Num_Adherent, Nom, Prenom, Adresse, Code_Postal, Ville, Telephone, Courriel, Date_Naissance
            FROM ADHERENT
            WHERE Nom LIKE :recherche1 
               OR Prenom LIKE :recherche2 
               OR Ville LIKE :recherche3
               OR Courriel LIKE :recherche4
            ORDER BY Nom, Prenom
        ');
        $requete->execute([
            'recherche1' => $termeRecherche,
            'recherche2' => $termeRecherche,
            'recherche3' => $termeRecherche,
            'recherche4' => $termeRecherche
        ]);
    } else {
        // Requête sans filtre
        $requete = $pdo->query('
            SELECT Num_Adherent, Nom, Prenom, Adresse, Code_Postal, Ville, Telephone, Courriel, Date_Naissance
            FROM ADHERENT
            ORDER BY Nom, Prenom
        ');
    }
    
    $listeAdherents = $requete->fetchAll();
    
} catch (PDOException $exception) {
    die('Erreur lors de la récupération des adhérents : ' . $exception->getMessage());
}

// Inclusion de l'en-tête
require_once 'header.php';
?>

<!-- Titre de la page -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-people"></i> <?php echo echapper($titrePage); ?></h1>
    <a href="adherent_formulaire.php" class="btn btn-success">
        <i class="bi bi-person-plus"></i> Ajouter un adhérent
    </a>
</div>

<!-- Message d'alerte -->
<?php echo $messageAlerte; ?>

<!-- Résultats de recherche -->
<?php if (!empty($recherche)): ?>
    <div class="alert alert-info">
        <i class="bi bi-search"></i> Résultats pour : <strong><?php echo echapper($recherche); ?></strong>
        <a href="adherents.php" class="btn btn-sm btn-outline-info ms-2">Effacer la recherche</a>
    </div>
<?php endif; ?>

<!-- Tableau des adhérents -->
<?php if (count($listeAdherents) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Nom</th>
                    <th scope="col">Prénom</th>
                    <th scope="col">Ville</th>
                    <th scope="col">Téléphone</th>
                    <th scope="col">Courriel</th>
                    <th scope="col">Date de naissance</th>
                    <th scope="col" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listeAdherents as $adherent): ?>
                    <tr>
                        <th scope="row"><?php echo echapper($adherent['Num_Adherent']); ?></th>
                        <td><?php echo echapper($adherent['Nom']); ?></td>
                        <td><?php echo echapper($adherent['Prenom']); ?></td>
                        <td><?php echo echapper($adherent['Ville']); ?></td>
                        <td><?php echo echapper($adherent['Telephone']); ?></td>
                        <td>
                            <?php if (!empty($adherent['Courriel'])): ?>
                                <a href="mailto:<?php echo echapper($adherent['Courriel']); ?>">
                                    <?php echo echapper($adherent['Courriel']); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if (!empty($adherent['Date_Naissance'])) {
                                $date = new DateTime($adherent['Date_Naissance']);
                                echo $date->format('d/m/Y');
                            }
                            ?>
                        </td>
                        <td class="table-actions text-center">
                            <!-- Bouton Modifier -->
                            <a href="adherent_formulaire.php?modifier=<?php echo $adherent['Num_Adherent']; ?>" 
                               class="btn btn-sm btn-warning" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            
                            <!-- Bouton Supprimer -->
                            <a href="adherents.php?supprimer=<?php echo $adherent['Num_Adherent']; ?>" 
                               class="btn btn-sm btn-danger" 
                               title="Supprimer"
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet adhérent ?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Compteur d'adhérents -->
    <p class="text-muted">
        <i class="bi bi-info-circle"></i> 
        <?php echo count($listeAdherents); ?> adhérent(s) trouvé(s)
    </p>
    
<?php else: ?>
    <!-- Message si aucun adhérent -->
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Aucun adhérent trouvé.
        <a href="adherent_formulaire.php" class="alert-link">Ajouter le premier adhérent</a>
    </div>
<?php endif; ?>

<?php
// Inclusion du pied de page
require_once 'footer.php';
?>
