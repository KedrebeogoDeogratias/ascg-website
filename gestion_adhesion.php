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
$remiseFamiliale = null; // Informations sur la remise familiale

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
    // VÉRIFICATION REMISE FAMILIALE (10%)
    // ============================================
    
    if (empty($erreurs)) {
        try {
            // Récupérer le tarif de l'activité sélectionnée
            $requeteTarif = $pdo->prepare('SELECT Tarif_Annuel, Libelle FROM ACTIVITE WHERE Num_Activite = :numActivite');
            $requeteTarif->execute(['numActivite' => $donneesFormulaire['numActivite']]);
            $activiteSelectionnee = $requeteTarif->fetch();
            $tarifNormal = $activiteSelectionnee['Tarif_Annuel'];
            $libelleActivite = $activiteSelectionnee['Libelle'];
            
            // Rechercher les membres de la famille (dans la table PARENTE)
            // On cherche dans LES DEUX SENS car le lien peut être enregistré dans un sens ou l'autre
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
                'numAdherent1' => $donneesFormulaire['numAdherent'],
                'numAdherent2' => $donneesFormulaire['numAdherent']
            ]);
            $membresParente = $requeteParente->fetchAll();
            
            // Pour chaque membre de la famille, vérifier s'il est inscrit à la même activité
            foreach ($membresParente as $parent) {
                $requeteInscription = $pdo->prepare('
                    SELECT COUNT(*) as inscrit 
                    FROM ADHESION 
                    WHERE Num_Adherent = :numParent AND Num_Activite = :numActivite
                ');
                $requeteInscription->execute([
                    'numParent' => $parent['Num_Parent'],
                    'numActivite' => $donneesFormulaire['numActivite']
                ]);
                $resultatInscription = $requeteInscription->fetch();
                
                if ($resultatInscription['inscrit'] > 0) {
                    // Un membre de la famille est inscrit à la même activité !
                    $remiseFamiliale = [
                        'applicable' => true,
                        'parentNom' => $parent['Nom'],
                        'parentPrenom' => $parent['Prenom'],
                        'natureRelation' => $parent['Nature'],
                        'activite' => $libelleActivite,
                        'tarifNormal' => $tarifNormal,
                        'tarifRemise' => $tarifNormal * 0.9,
                        'montantRemise' => $tarifNormal * 0.1
                    ];
                    break; // On s'arrête au premier parent trouvé
                }
            }
            
        } catch (PDOException $exception) {
            // En cas d'erreur, on continue sans la vérification de remise
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

<!-- Alerte Remise Familiale -->
<?php if ($remiseFamiliale !== null && $remiseFamiliale['applicable']): ?>
    <div class="alert alert-success alert-dismissible fade show border-3" role="alert" style="border-left: 5px solid #198754;">
        <h4 class="alert-heading">
            <i class="bi bi-gift-fill"></i> Remise Familiale Applicable !
        </h4>
        <hr>
        <p class="mb-2">
            <i class="bi bi-people-fill"></i> 
            <strong><?php echo echapper($remiseFamiliale['parentPrenom'] . ' ' . $remiseFamiliale['parentNom']); ?></strong> 
            (<?php echo echapper($remiseFamiliale['natureRelation']); ?>) 
            est déjà inscrit(e) à l'activité <strong>"<?php echo echapper($remiseFamiliale['activite']); ?>"</strong>.
        </p>
        <p class="mb-3">
            <i class="bi bi-percent"></i> Une <strong>remise de 10%</strong> est accordée pour cette inscription !
        </p>
        <div class="row">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center py-2">
                        <small class="text-muted">Tarif Normal</small>
                        <h5 class="mb-0 text-decoration-line-through text-secondary">
                            <?php echo number_format($remiseFamiliale['tarifNormal'], 2, ',', ' '); ?> €
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center py-2">
                        <small>Tarif Remisé</small>
                        <h5 class="mb-0">
                            <i class="bi bi-check-circle"></i> 
                            <?php echo number_format($remiseFamiliale['tarifRemise'], 2, ',', ' '); ?> €
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning">
                    <div class="card-body text-center py-2">
                        <small>Économie</small>
                        <h5 class="mb-0">
                            - <?php echo number_format($remiseFamiliale['montantRemise'], 2, ',', ' '); ?> €
                        </h5>
                    </div>
                </div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
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
                    
                    <!-- Montant à payer (calculé dynamiquement) -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="montantAPayer" class="form-label">
                                <i class="bi bi-currency-euro"></i> Montant à payer
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control form-control-lg text-end fw-bold" 
                                       id="montantAPayer" 
                                       name="montantAPayer"
                                       value="0,00" 
                                       readonly
                                       style="background-color: #e9f7ef;">
                                <span class="input-group-text">€</span>
                            </div>
                            <div class="form-text" id="montantInfo">
                                <i class="bi bi-info-circle"></i> Sélectionnez un adhérent et une activité pour calculer le montant.
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <!-- Zone d'alerte remise dynamique -->
                            <div id="alerteRemiseDynamique" style="display: none;"></div>
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

<!-- Script JavaScript pour le calcul dynamique du tarif -->
<script>
/**
 * Calcul dynamique du tarif avec remise familiale
 */

// Références aux éléments du DOM
const selectAdherent = document.getElementById('numAdherent');
const selectActivite = document.getElementById('numActivite');
const champMontant = document.getElementById('montantAPayer');
const montantInfo = document.getElementById('montantInfo');
const alerteRemise = document.getElementById('alerteRemiseDynamique');

/**
 * Formate un nombre en format monétaire français
 */
function formaterMontant(montant) {
    return parseFloat(montant).toLocaleString('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Appelle l'API pour calculer le tarif
 */
async function calculerTarif() {
    const idAdherent = selectAdherent.value;
    const idActivite = selectActivite.value;
    
    // Réinitialiser si aucune sélection
    if (!idAdherent || !idActivite) {
        champMontant.value = '0,00';
        champMontant.style.backgroundColor = '#e9f7ef';
        montantInfo.innerHTML = '<i class="bi bi-info-circle"></i> Sélectionnez un adhérent et une activité pour calculer le montant.';
        alerteRemise.style.display = 'none';
        return;
    }
    
    // Afficher un indicateur de chargement
    champMontant.value = 'Calcul...';
    montantInfo.innerHTML = '<i class="bi bi-hourglass-split"></i> Vérification de la remise familiale...';
    
    try {
        // Appel à l'API
        const response = await fetch(`api_get_tarif.php?id_adherent=${idAdherent}&id_activite=${idActivite}`);
        const data = await response.json();
        
        if (data.erreur) {
            champMontant.value = 'Erreur';
            montantInfo.innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i> ' + data.message;
            alerteRemise.style.display = 'none';
            return;
        }
        
        // Mettre à jour le montant
        champMontant.value = formaterMontant(data.montant);
        
        // Vérifier si une remise s'applique
        if (data.remise) {
            // Afficher l'alerte de remise
            champMontant.style.backgroundColor = '#d4edda'; // Vert clair
            champMontant.classList.add('text-success');
            
            montantInfo.innerHTML = `
                <span class="text-success">
                    <i class="bi bi-check-circle-fill"></i> 
                    Remise familiale de ${data.pourcentageRemise}% appliquée !
                    <span class="text-decoration-line-through text-muted">${formaterMontant(data.montantOriginal)} €</span>
                </span>
            `;
            
            alerteRemise.innerHTML = `
                <div class="alert alert-success py-2 mb-0">
                    <i class="bi bi-gift-fill"></i> <strong>Remise Familiale</strong><br>
                    <small>
                        <i class="bi bi-person-fill"></i> ${data.parentPrenom} ${data.parentNom} (${data.natureRelation})
                        est inscrit(e) à "${data.libelleActivite}".<br>
                        <strong>Économie : ${formaterMontant(data.economie)} €</strong>
                    </small>
                </div>
            `;
            alerteRemise.style.display = 'block';
            
        } else {
            // Pas de remise
            champMontant.style.backgroundColor = '#e9f7ef';
            champMontant.classList.remove('text-success');
            
            montantInfo.innerHTML = `
                <i class="bi bi-info-circle"></i> 
                Tarif standard pour "${data.libelleActivite}"
            `;
            
            alerteRemise.style.display = 'none';
        }
        
    } catch (error) {
        console.error('Erreur API:', error);
        champMontant.value = 'Erreur';
        montantInfo.innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i> Erreur de communication avec le serveur.';
        alerteRemise.style.display = 'none';
    }
}

// Écouter les changements sur les listes déroulantes
selectAdherent.addEventListener('change', calculerTarif);
selectActivite.addEventListener('change', calculerTarif);

// Calculer au chargement si des valeurs sont déjà sélectionnées
document.addEventListener('DOMContentLoaded', function() {
    if (selectAdherent.value && selectActivite.value) {
        calculerTarif();
    }
});
</script>

<?php
// Inclusion du pied de page
require_once 'footer.php';
?>
