<?php
/**
 * Liste des sections
 * Association Sportive - Projet ASCG
 */

// Titre de la page
$titrePage = 'Liste des sections';

// Inclusion de la configuration
require_once 'config.php';

// ============================================
// TRAITEMENT DE LA SUPPRESSION (si demandée)
// ============================================

$messageAlerte = '';

if (isset($_GET['supprimer']) && !empty($_GET['supprimer'])) {
    $codeSection = trim($_GET['supprimer']);
    
    try {
        // Vérifier si des activités existent pour cette section
        $requeteVerif = $pdo->prepare('SELECT COUNT(*) as total FROM ACTIVITE WHERE Code_Section = :code');
        $requeteVerif->execute(['code' => $codeSection]);
        $resultat = $requeteVerif->fetch();
        
        if ($resultat['total'] > 0) {
            $messageAlerte = afficherAlerte('Impossible de supprimer cette section : ' . $resultat['total'] . ' activité(s) existante(s).', 'warning');
        } else {
            // Requête de suppression
            $requete = $pdo->prepare('DELETE FROM SECTION WHERE Code_Section = :code');
            $requete->execute(['code' => $codeSection]);
            
            if ($requete->rowCount() > 0) {
                $messageAlerte = afficherAlerte('Section supprimée avec succès.', 'success');
            } else {
                $messageAlerte = afficherAlerte('Section non trouvée.', 'warning');
            }
        }
    } catch (PDOException $exception) {
        $messageAlerte = afficherAlerte('Erreur lors de la suppression : ' . $exception->getMessage(), 'danger');
    }
}

// ============================================
// RÉCUPÉRATION DES SECTIONS
// ============================================

try {
    $requete = $pdo->query('
        SELECT s.Code_Section, s.Libelle, s.Debut_Saison, s.Id_Benevole,
               b.Nom AS NomResponsable, b.Prenom AS PrenomResponsable,
               (SELECT COUNT(*) FROM ACTIVITE a WHERE a.Code_Section = s.Code_Section) AS NombreActivites,
               (SELECT COUNT(*) FROM BUREAU bu WHERE bu.Code_Section = s.Code_Section) AS NombreBureau
        FROM SECTION s
        LEFT JOIN BENEVOLE b ON s.Id_Benevole = b.Id_Benevole
        ORDER BY s.Libelle
    ');
    $listeSections = $requete->fetchAll();
    
} catch (PDOException $exception) {
    die('Erreur lors de la récupération des sections : ' . $exception->getMessage());
}

require_once 'header.php';
?>

<!-- Titre de la page -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-diagram-3"></i> <?php echo echapper($titrePage); ?></h1>
    <a href="section_formulaire.php" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Ajouter une section
    </a>
</div>

<?php echo $messageAlerte; ?>

<!-- Tableau des sections -->
<?php if (count($listeSections) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th scope="col">Code</th>
                    <th scope="col">Libellé</th>
                    <th scope="col">Responsable</th>
                    <th scope="col">Début de saison</th>
                    <th scope="col">Activités</th>
                    <th scope="col">Membres bureau</th>
                    <th scope="col" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listeSections as $section): ?>
                    <tr>
                        <th scope="row">
                            <span class="badge bg-warning text-dark"><?php echo echapper($section['Code_Section']); ?></span>
                        </th>
                        <td><strong><?php echo echapper($section['Libelle']); ?></strong></td>
                        <td>
                            <?php if (!empty($section['NomResponsable'])): ?>
                                <?php echo echapper($section['PrenomResponsable'] . ' ' . $section['NomResponsable']); ?>
                            <?php else: ?>
                                <span class="text-muted">Non assigné</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if (!empty($section['Debut_Saison'])) {
                                $date = new DateTime($section['Debut_Saison']);
                                echo $date->format('d/m/Y');
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success"><?php echo $section['NombreActivites']; ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info"><?php echo $section['NombreBureau']; ?></span>
                        </td>
                        <td class="table-actions text-center">
                            <a href="section_formulaire.php?modifier=<?php echo urlencode($section['Code_Section']); ?>" 
                               class="btn btn-sm btn-warning" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="sections.php?supprimer=<?php echo urlencode($section['Code_Section']); ?>" 
                               class="btn btn-sm btn-danger" 
                               title="Supprimer"
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette section ?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <p class="text-muted">
        <i class="bi bi-info-circle"></i> 
        <?php echo count($listeSections); ?> section(s) trouvée(s)
    </p>
    
<?php else: ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Aucune section trouvée.
        <a href="section_formulaire.php" class="alert-link">Ajouter la première section</a>
    </div>
<?php endif; ?>

<?php
require_once 'footer.php';
?>
