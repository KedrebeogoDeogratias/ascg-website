<?php
/**
 * Liste des inscriptions (adhésions)
 * Association Sportive - Projet ASCG
 */

// Titre de la page
$titrePage = 'Liste des inscriptions';

// Inclusion de la configuration
require_once 'config.php';

// ============================================
// TRAITEMENT DE LA SUPPRESSION (si demandée)
// ============================================

$messageAlerte = '';

if (isset($_GET['supprimer_adherent']) && isset($_GET['supprimer_activite'])) {
    $numAdherent = (int) $_GET['supprimer_adherent'];
    $numActivite = (int) $_GET['supprimer_activite'];
    
    try {
        // Requête de suppression
        $requete = $pdo->prepare('
            DELETE FROM ADHESION 
            WHERE Num_Adherent = :numAdherent AND Num_Activite = :numActivite
        ');
        $requete->execute([
            'numAdherent' => $numAdherent,
            'numActivite' => $numActivite
        ]);
        
        if ($requete->rowCount() > 0) {
            $messageAlerte = afficherAlerte('Inscription supprimée avec succès.', 'success');
        } else {
            $messageAlerte = afficherAlerte('Inscription non trouvée.', 'warning');
        }
    } catch (PDOException $exception) {
        $messageAlerte = afficherAlerte('Erreur lors de la suppression : ' . $exception->getMessage(), 'danger');
    }
}

// ============================================
// FILTRES DE RECHERCHE
// ============================================

$filtreSection = isset($_GET['section']) ? trim($_GET['section']) : '';
$filtreActivite = isset($_GET['activite']) ? (int) $_GET['activite'] : 0;

// ============================================
// RÉCUPÉRATION DES INSCRIPTIONS
// ============================================

try {
    // Construction de la requête avec jointures
    $sql = '
        SELECT 
            ad.Num_Adherent,
            ad.Num_Activite,
            ad.Date_Adhesion,
            ad.Nom_Banque,
            ad.NumCheque,
            a.Nom AS NomAdherent,
            a.Prenom AS PrenomAdherent,
            a.Telephone,
            act.Libelle AS LibelleActivite,
            act.Jour_Semaine,
            act.Tarif_Annuel,
            s.Code_Section,
            s.Libelle AS LibelleSection
        FROM ADHESION ad
        INNER JOIN ADHERENT a ON ad.Num_Adherent = a.Num_Adherent
        INNER JOIN ACTIVITE act ON ad.Num_Activite = act.Num_Activite
        LEFT JOIN SECTION s ON act.Code_Section = s.Code_Section
        WHERE 1=1
    ';
    
    $parametres = [];
    
    // Filtre par section
    if (!empty($filtreSection)) {
        $sql .= ' AND s.Code_Section = :section';
        $parametres['section'] = $filtreSection;
    }
    
    // Filtre par activité
    if ($filtreActivite > 0) {
        $sql .= ' AND act.Num_Activite = :activite';
        $parametres['activite'] = $filtreActivite;
    }
    
    $sql .= ' ORDER BY ad.Date_Adhesion DESC, a.Nom, a.Prenom';
    
    $requete = $pdo->prepare($sql);
    $requete->execute($parametres);
    $listeInscriptions = $requete->fetchAll();
    
    // Récupération des sections pour le filtre
    $requeteSections = $pdo->query('SELECT Code_Section, Libelle FROM SECTION ORDER BY Libelle');
    $listeSections = $requeteSections->fetchAll();
    
    // Récupération des activités pour le filtre
    $requeteActivites = $pdo->query('SELECT Num_Activite, Libelle FROM ACTIVITE ORDER BY Libelle');
    $listeActivites = $requeteActivites->fetchAll();
    
    // Calcul du total des tarifs
    $totalTarifs = array_sum(array_column($listeInscriptions, 'Tarif_Annuel'));
    
} catch (PDOException $exception) {
    die('Erreur lors de la récupération des inscriptions : ' . $exception->getMessage());
}

// Inclusion de l'en-tête
require_once 'header.php';
?>

<!-- Titre de la page -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-card-checklist"></i> <?php echo echapper($titrePage); ?></h1>
    <a href="gestion_adhesion.php" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Nouvelle inscription
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
        <form method="get" action="inscriptions.php" class="row g-3">
            <!-- Filtre par section -->
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
            
            <!-- Filtre par activité -->
            <div class="col-md-4">
                <label for="activite" class="form-label">Activité</label>
                <select class="form-select" id="activite" name="activite">
                    <option value="">Toutes les activités</option>
                    <?php foreach ($listeActivites as $activite): ?>
                        <option value="<?php echo $activite['Num_Activite']; ?>"
                                <?php echo $filtreActivite === (int)$activite['Num_Activite'] ? 'selected' : ''; ?>>
                            <?php echo echapper($activite['Libelle']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Boutons -->
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filtrer
                </button>
                <a href="inscriptions.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tableau des inscriptions -->
<?php if (count($listeInscriptions) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th scope="col">Adhérent</th>
                    <th scope="col">Activité</th>
                    <th scope="col">Section</th>
                    <th scope="col">Jour</th>
                    <th scope="col">Date inscription</th>
                    <th scope="col">Tarif</th>
                    <th scope="col">Paiement</th>
                    <th scope="col" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listeInscriptions as $inscription): ?>
                    <tr>
                        <td>
                            <strong><?php echo echapper($inscription['NomAdherent'] . ' ' . $inscription['PrenomAdherent']); ?></strong>
                            <?php if (!empty($inscription['Telephone'])): ?>
                                <br><small class="text-muted"><?php echo echapper($inscription['Telephone']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo echapper($inscription['LibelleActivite']); ?></td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo echapper($inscription['LibelleSection'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td><?php echo echapper($inscription['Jour_Semaine']); ?></td>
                        <td>
                            <?php 
                            if (!empty($inscription['Date_Adhesion'])) {
                                $date = new DateTime($inscription['Date_Adhesion']);
                                echo $date->format('d/m/Y');
                            }
                            ?>
                        </td>
                        <td class="text-end">
                            <strong><?php echo number_format($inscription['Tarif_Annuel'], 2, ',', ' '); ?> €</strong>
                        </td>
                        <td>
                            <?php if (!empty($inscription['Nom_Banque']) || !empty($inscription['NumCheque'])): ?>
                                <?php echo echapper($inscription['Nom_Banque']); ?>
                                <?php if (!empty($inscription['NumCheque'])): ?>
                                    <br><small class="text-muted">N° <?php echo echapper($inscription['NumCheque']); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="table-actions text-center">
                            <!-- Bouton Supprimer -->
                            <a href="inscriptions.php?supprimer_adherent=<?php echo $inscription['Num_Adherent']; ?>&supprimer_activite=<?php echo $inscription['Num_Activite']; ?>" 
                               class="btn btn-sm btn-danger" 
                               title="Supprimer l'inscription"
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette inscription ?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-secondary">
                <tr>
                    <td colspan="5" class="text-end"><strong>Total :</strong></td>
                    <td class="text-end"><strong><?php echo number_format($totalTarifs, 2, ',', ' '); ?> €</strong></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <!-- Compteur d'inscriptions -->
    <p class="text-muted">
        <i class="bi bi-info-circle"></i> 
        <?php echo count($listeInscriptions); ?> inscription(s) trouvée(s)
    </p>
    
<?php else: ?>
    <!-- Message si aucune inscription -->
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Aucune inscription trouvée.
        <a href="gestion_adhesion.php" class="alert-link">Créer une première inscription</a>
    </div>
<?php endif; ?>

<?php
// Inclusion du pied de page
require_once 'footer.php';
?>
