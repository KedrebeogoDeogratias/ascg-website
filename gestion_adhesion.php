<?php
/**
 * Gestion des adhésions (inscriptions aux activités)
 * Association Sportive - Projet ASCG
 */

// Titre de la page
$titrePage = 'Nouvelle inscription';

// Inclusion de la configuration
require_once 'config.php';

// ============================================
// VARIABLES DU FORMULAIRE
// ============================================

$messageAlerte = '';
$erreurs = [];

// Valeurs par défaut du formulaire
$donneesFormulaire = [
    'numAdherent' => '',
    'numActivite' => '',
    'dateAdhesion' => date('Y-m-d'),
    'nomBanque' => '',
    'numCheque' => ''
];

// ============================================
// RÉCUPÉRATION DES LISTES POUR LES SELECT
// ============================================

try {
    // Liste des adhérents (Nom + Prénom)
    $requeteAdherents = $pdo->query('
        SELECT Num_Adherent, Nom, Prenom 
        FROM ADHERENT 
        ORDER BY Nom, Prenom
    ');
    $listeAdherents = $requeteAdherents->fetchAll();
    
    // Liste des activités (Libellé + Jour)
    $requeteActivites = $pdo->query('
        SELECT a.Num_Activite, a.Libelle, a.Jour_Semaine, a.Tarif_Annuel, s.Libelle AS NomSection
        FROM ACTIVITE a
        LEFT JOIN SECTION s ON a.Code_Section = s.Code_Section
        ORDER BY s.Libelle, a.Libelle
    ');
    $listeActivites = $requeteActivites->fetchAll();
    
} catch (PDOException $exception) {
    die('Erreur lors de la récupération des données : ' . $exception->getMessage());
}

// ============================================
// TRAITEMENT DU FORMULAIRE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération et nettoyage des données
    $donneesFormulaire = [
        'numAdherent' => isset($_POST['numAdherent']) ? (int) $_POST['numAdherent'] : '',
        'numActivite' => isset($_POST['numActivite']) ? (int) $_POST['numActivite'] : '',
        'dateAdhesion' => trim($_POST['dateAdhesion'] ?? ''),
        'nomBanque' => trim($_POST['nomBanque'] ?? ''),
        'numCheque' => trim($_POST['numCheque'] ?? '')
    ];
    
    // ============================================
    // VALIDATION DES DONNÉES
    // ============================================
    
    // Adhérent obligatoire
    if (empty($donneesFormulaire['numAdherent'])) {
        $erreurs['numAdherent'] = 'Veuillez sélectionner un adhérent.';
    }
    
    // Activité obligatoire
    if (empty($donneesFormulaire['numActivite'])) {
        $erreurs['numActivite'] = 'Veuillez sélectionner une activité.';
    }
    
    // Date d'adhésion obligatoire et valide
    if (empty($donneesFormulaire['dateAdhesion'])) {
        $erreurs['dateAdhesion'] = 'La date d\'inscription est obligatoire.';
    } else {
        $dateTest = DateTime::createFromFormat('Y-m-d', $donneesFormulaire['dateAdhesion']);
        if (!$dateTest) {
            $erreurs['dateAdhesion'] = 'La date d\'inscription n\'est pas valide.';
        }
    }
    
    // Vérification que l'adhésion n'existe pas déjà
    if (empty($erreurs)) {
        try {
            $requeteVerif = $pdo->prepare('
                SELECT COUNT(*) as existe 
                FROM ADHESION 
                WHERE Num_Adherent = :numAdherent AND Num_Activite = :numActivite
            ');
            $requeteVerif->execute([
                'numAdherent' => $donneesFormulaire['numAdherent'],
                'numActivite' => $donneesFormulaire['numActivite']
            ]);
            $resultat = $requeteVerif->fetch();
            
            if ($resultat['existe'] > 0) {
                $erreurs['doublon'] = 'Cet adhérent est déjà inscrit à cette activité.';
            }
        } catch (PDOException $exception) {
            $erreurs['bdd'] = 'Erreur lors de la vérification : ' . $exception->getMessage();
        }
    }
    
    // ============================================
    // ENREGISTREMENT SI PAS D'ERREURS
    // ============================================
    
    if (empty($erreurs)) {
        try {
            // Requête d'insertion
            $requete = $pdo->prepare('
                INSERT INTO ADHESION (Num_Adherent, Num_Activite, Date_Adhesion, Nom_Banque, NumCheque)
                VALUES (:numAdherent, :numActivite, :dateAdhesion, :nomBanque, :numCheque)
            ');
            $requete->execute([
                'numAdherent' => $donneesFormulaire['numAdherent'],
                'numActivite' => $donneesFormulaire['numActivite'],
                'dateAdhesion' => $donneesFormulaire['dateAdhesion'],
                'nomBanque' => !empty($donneesFormulaire['nomBanque']) ? $donneesFormulaire['nomBanque'] : null,
                'numCheque' => !empty($donneesFormulaire['numCheque']) ? $donneesFormulaire['numCheque'] : null
            ]);
            
            $messageAlerte = afficherAlerte('Inscription enregistrée avec succès !', 'success');
            
            // Réinitialisation du formulaire après ajout
            $donneesFormulaire = [
                'numAdherent' => '',
                'numActivite' => '',
                'dateAdhesion' => date('Y-m-d'),
                'nomBanque' => '',
                'numCheque' => ''
            ];
            
        } catch (PDOException $exception) {
            $messageAlerte = afficherAlerte('Erreur lors de l\'enregistrement : ' . $exception->getMessage(), 'danger');
        }
    } else {
        $messageAlerte = afficherAlerte('Veuillez corriger les erreurs ci-dessous.', 'danger');
    }
}

// Inclusion de l'en-tête
require_once 'header.php';
?>

<!-- Titre de la page -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="bi bi-person-plus-fill"></i> <?php echo echapper($titrePage); ?>
    </h1>
    <a href="inscriptions.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Voir toutes les inscriptions
    </a>
</div>

<!-- Message d'alerte -->
<?php echo $messageAlerte; ?>

<!-- Erreur doublon -->
<?php if (isset($erreurs['doublon'])): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> <?php echo echapper($erreurs['doublon']); ?>
    </div>
<?php endif; ?>

<!-- Formulaire d'inscription -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-card-checklist"></i> Formulaire d'inscription à une activité
            </div>
            <div class="card-body">
                <form method="post" action="gestion_adhesion.php" novalidate>
                    
                    <!-- Section Adhérent et Activité -->
                    <h5 class="mb-3"><i class="bi bi-person"></i> Sélection</h5>
                    
                    <div class="row">
                        <!-- Liste des adhérents -->
                        <div class="col-md-6 mb-3">
                            <label for="numAdherent" class="form-label">
                                Adhérent <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?php echo isset($erreurs['numAdherent']) ? 'is-invalid' : ''; ?>" 
                                    id="numAdherent" 
                                    name="numAdherent" 
                                    required>
                                <option value="">-- Sélectionnez un adhérent --</option>
                                <?php foreach ($listeAdherents as $adherent): ?>
                                    <option value="<?php echo $adherent['Num_Adherent']; ?>"
                                            <?php echo $donneesFormulaire['numAdherent'] == $adherent['Num_Adherent'] ? 'selected' : ''; ?>>
                                        <?php echo echapper($adherent['Nom'] . ' ' . $adherent['Prenom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($erreurs['numAdherent'])): ?>
                                <div class="invalid-feedback"><?php echo echapper($erreurs['numAdherent']); ?></div>
                            <?php endif; ?>
                            <div class="form-text">
                                <a href="adherent_formulaire.php" class="text-decoration-none">
                                    <i class="bi bi-plus-circle"></i> Ajouter un nouvel adhérent
                                </a>
                            </div>
                        </div>
                        
                        <!-- Liste des activités -->
                        <div class="col-md-6 mb-3">
                            <label for="numActivite" class="form-label">
                                Activité <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?php echo isset($erreurs['numActivite']) ? 'is-invalid' : ''; ?>" 
                                    id="numActivite" 
                                    name="numActivite" 
                                    required>
                                <option value="">-- Sélectionnez une activité --</option>
                                <?php 
                                $sectionCourante = '';
                                foreach ($listeActivites as $activite): 
                                    // Grouper par section
                                    if ($activite['NomSection'] !== $sectionCourante):
                                        if ($sectionCourante !== '') echo '</optgroup>';
                                        $sectionCourante = $activite['NomSection'];
                                        echo '<optgroup label="' . echapper($sectionCourante ?? 'Sans section') . '">';
                                    endif;
                                ?>
                                    <option value="<?php echo $activite['Num_Activite']; ?>"
                                            data-tarif="<?php echo $activite['Tarif_Annuel']; ?>"
                                            <?php echo $donneesFormulaire['numActivite'] == $activite['Num_Activite'] ? 'selected' : ''; ?>>
                                        <?php echo echapper($activite['Libelle'] . ' - ' . $activite['Jour_Semaine']); ?>
                                        (<?php echo number_format($activite['Tarif_Annuel'], 2, ',', ' '); ?> €)
                                    </option>
                                <?php endforeach; ?>
                                <?php if ($sectionCourante !== '') echo '</optgroup>'; ?>
                            </select>
                            <?php if (isset($erreurs['numActivite'])): ?>
                                <div class="invalid-feedback"><?php echo echapper($erreurs['numActivite']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Section Paiement -->
                    <h5 class="mb-3"><i class="bi bi-credit-card"></i> Informations de paiement</h5>
                    
                    <div class="row">
                        <!-- Date d'inscription -->
                        <div class="col-md-4 mb-3">
                            <label for="dateAdhesion" class="form-label">
                                Date d'inscription <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   class="form-control <?php echo isset($erreurs['dateAdhesion']) ? 'is-invalid' : ''; ?>" 
                                   id="dateAdhesion" 
                                   name="dateAdhesion" 
                                   value="<?php echo echapper($donneesFormulaire['dateAdhesion']); ?>" 
                                   required>
                            <?php if (isset($erreurs['dateAdhesion'])): ?>
                                <div class="invalid-feedback"><?php echo echapper($erreurs['dateAdhesion']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Nom de la banque -->
                        <div class="col-md-4 mb-3">
                            <label for="nomBanque" class="form-label">Nom de la banque</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="nomBanque" 
                                   name="nomBanque" 
                                   value="<?php echo echapper($donneesFormulaire['nomBanque']); ?>" 
                                   maxlength="100"
                                   placeholder="Ex: Crédit Agricole">
                        </div>
                        
                        <!-- Numéro de chèque -->
                        <div class="col-md-4 mb-3">
                            <label for="numCheque" class="form-label">Numéro de chèque</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="numCheque" 
                                   name="numCheque" 
                                   value="<?php echo echapper($donneesFormulaire['numCheque']); ?>" 
                                   maxlength="50"
                                   placeholder="Ex: CHQ001234">
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Boutons de soumission -->
                    <div class="d-flex justify-content-between">
                        <a href="inscriptions.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle"></i> Valider l'inscription
                        </button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>
    
    <!-- Panneau d'information -->
    <div class="col-lg-4">
        <div class="card bg-light">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Informations
            </div>
            <div class="card-body">
                <p class="card-text">
                    <strong>Saison :</strong> 2025-2026
                </p>
                <hr>
                <h6>Instructions :</h6>
                <ol class="small">
                    <li>Sélectionnez l'adhérent concerné</li>
                    <li>Choisissez l'activité souhaitée</li>
                    <li>Renseignez les informations de paiement</li>
                    <li>Validez l'inscription</li>
                </ol>
                <hr>
                <p class="text-muted small mb-0">
                    <i class="bi bi-exclamation-triangle"></i> 
                    Un adhérent ne peut être inscrit qu'une seule fois à chaque activité.
                </p>
            </div>
        </div>
        
        <!-- Statistiques rapides -->
        <div class="card mt-3">
            <div class="card-header bg-secondary text-white">
                <i class="bi bi-bar-chart"></i> Statistiques
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span>Adhérents disponibles</span>
                    <span class="badge bg-primary"><?php echo count($listeAdherents); ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Activités proposées</span>
                    <span class="badge bg-success"><?php echo count($listeActivites); ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>

<p class="text-muted mt-3">
    <small><span class="text-danger">*</span> Champs obligatoires</small>
</p>

<?php
// Inclusion du pied de page
require_once 'footer.php';
?>
