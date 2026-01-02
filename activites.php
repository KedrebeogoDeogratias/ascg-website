<?php
/**
 * Liste des activités
 * Association Sportive - Projet ASCG
 */

// Titre de la page
$titrePage = 'Liste des activités';

// Inclusion de la configuration
require_once 'config.php';

// ============================================
// TRAITEMENT DE LA SUPPRESSION (si demandée)
// ============================================

$messageAlerte = '';

if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
    $numeroActivite = (int) $_GET['supprimer'];
    
    try {
        // Vérifier si des adhésions existent pour cette activité
        $requeteVerif = $pdo->prepare('SELECT COUNT(*) as total FROM ADHESION WHERE Num_Activite = :numero');
        $requeteVerif->execute(['numero' => $numeroActivite]);
        $resultat = $requeteVerif->fetch();
        
        if ($resultat['total'] > 0) {
            $messageAlerte = afficherAlerte('Impossible de supprimer cette activité : ' . $resultat['total'] . ' inscription(s) existante(s).', 'warning');
        } else {
            // Requête de suppression
            $requete = $pdo->prepare('DELETE FROM ACTIVITE WHERE Num_Activite = :numero');
            $requete->execute(['numero' => $numeroActivite]);
            
            if ($requete->rowCount() > 0) {
                $messageAlerte = afficherAlerte('Activité supprimée avec succès.', 'success');
            } else {
                $messageAlerte = afficherAlerte('Activité non trouvée.', 'warning');
            }
        }
    } catch (PDOException $exception) {
        $messageAlerte = afficherAlerte('Erreur lors de la suppression : ' . $exception->getMessage(), 'danger');
    }
}

// ============================================
// FILTRES
// ============================================

$filtreSection = isset($_GET['section']) ? trim($_GET['section']) : '';

// ============================================
// RÉCUPÉRATION DES ACTIVITÉS
// ============================================

try {
    $sql = '
        SELECT a.Num_Activite, a.Libelle, a.Description, a.Jour_Semaine, a.Horaire, 
               a.Duree_Seance, a.Tarif_Annuel, a.Code_Section, a.Id_Lieu,
               s.Libelle AS NomSection,
               l.Nom AS NomLieu,
               (SELECT COUNT(*) FROM ADHESION ad WHERE ad.Num_Activite = a.Num_Activite) AS NombreInscrits
        FROM ACTIVITE a
        LEFT JOIN SECTION s ON a.Code_Section = s.Code_Section
        LEFT JOIN LIEU l ON a.Id_Lieu = l.Id_Lieu
        WHERE 1=1
    ';
    
    $parametres = [];
    
    if (!empty($filtreSection)) {
        $sql .= ' AND a.Code_Section = :section';
        $parametres['section'] = $filtreSection;
    }
    
    $sql .= ' ORDER BY s.Libelle, a.Jour_Semaine, a.Horaire';
    
    $requete = $pdo->prepare($sql);
    $requete->execute($parametres);
    $listeActivites = $requete->fetchAll();
    
    // Récupération des sections pour le filtre
    $requeteSections = $pdo->query('SELECT Code_Section, Libelle FROM SECTION ORDER BY Libelle');
    $listeSections = $requeteSections->fetchAll();
    
} catch (PDOException $exception) {
    die('Erreur lors de la récupération des activités : ' . $exception->getMessage());
}

// Inclusion de l'en-tête
require_once 'header.php';
?>

<!-- Titre de la page -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-calendar-event"></i> <?php echo echapper($titrePage); ?></h1>
    <a href="activite_formulaire.php" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Ajouter une activité
    </a>
</div>

<!-- Message d'alerte -->
<?php echo $messageAlerte; ?>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <i class="bi bi-funnel"></i> Filtres
    </div>
    <div class="card-body">
        <form method="get" action="activites.php" class="row g-3">
            <div class="col-md-4">
                <label for="section" class="form-label">Section</label>
                <select class="form-select" id="section" name="section">
                    <option value="">Toutes les sections</option>
                    <?php foreach ($listeSections as $section): ?>
                        <option value="<?php echo echapper($section['Code_Section']); ?>"
                                <?php echo $filtreSection === $section['Code_Section'] ? 'selected' : ''; ?>>
                            <?php echo echapper($section['Libelle']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                <a href="activites.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tableau des activités -->
<?php if (count($listeActivites) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Activité</th>
                    <th scope="col">Section</th>
                    <th scope="col">Jour</th>
                    <th scope="col">Horaire</th>
                    <th scope="col">Durée</th>
                    <th scope="col">Lieu</th>
                    <th scope="col">Tarif annuel</th>
                    <th scope="col">Inscrits</th>
                    <th scope="col" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listeActivites as $activite): ?>
                    <tr>
                        <th scope="row"><?php echo echapper($activite['Num_Activite']); ?></th>
                        <td>
                            <strong><?php echo echapper($activite['Libelle']); ?></strong>
                            <?php if (!empty($activite['Description'])): ?>
                                <br><small class="text-muted"><?php echo echapper(substr($activite['Description'], 0, 50)); ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo echapper($activite['NomSection'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td><?php echo echapper($activite['Jour_Semaine']); ?></td>
                        <td>
                            <?php 
                            if (!empty($activite['Horaire'])) {
                                echo date('H:i', strtotime($activite['Horaire']));
                            }
                            ?>
                        </td>
                        <td><?php echo echapper($activite['Duree_Seance']); ?> min</td>
                        <td><?php echo echapper($activite['NomLieu'] ?? 'N/A'); ?></td>
                        <td class="text-end"><?php echo number_format($activite['Tarif_Annuel'], 2, ',', ' '); ?> €</td>
                        <td class="text-center">
                            <span class="badge bg-primary"><?php echo $activite['NombreInscrits']; ?></span>
                        </td>
                        <td class="table-actions text-center">
                            <a href="activite_formulaire.php?modifier=<?php echo $activite['Num_Activite']; ?>" 
                               class="btn btn-sm btn-warning" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="activites.php?supprimer=<?php echo $activite['Num_Activite']; ?>" 
                               class="btn btn-sm btn-danger" 
                               title="Supprimer"
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette activité ?');">
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
        <?php echo count($listeActivites); ?> activité(s) trouvée(s)
    </p>
    
<?php else: ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Aucune activité trouvée.
        <a href="activite_formulaire.php" class="alert-link">Ajouter la première activité</a>
    </div>
<?php endif; ?>

<?php
require_once 'footer.php';
?>
