<?php
/**
 * Formulaire d'ajout/modification d'une activité
 * Association Sportive - Projet ASCG
 */

// Inclusion de la configuration
require_once 'config.php';

// ============================================
// VARIABLES DU FORMULAIRE
// ============================================

$modeModification = false;
$numeroActivite = null;
$messageAlerte = '';
$erreurs = [];

// Valeurs par défaut du formulaire
$donneesFormulaire = [
    'libelle' => '',
    'description' => '',
    'jourSemaine' => '',
    'horaire' => '',
    'dureeSeance' => '',
    'tarifAnnuel' => '',
    'codeSection' => '',
    'idLieu' => ''
];

// Liste des jours de la semaine
$joursSemaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

// ============================================
// RÉCUPÉRATION DES LISTES POUR LES SELECT
// ============================================

try {
    // Liste des sections
    $requeteSections = $pdo->query('SELECT Code_Section, Libelle FROM SECTION ORDER BY Libelle');
    $listeSections = $requeteSections->fetchAll();
    
    // Liste des lieux
    $requeteLieux = $pdo->query('SELECT Id_Lieu, Nom, Capacite FROM LIEU ORDER BY Nom');
    $listeLieux = $requeteLieux->fetchAll();
    
} catch (PDOException $exception) {
    die('Erreur lors de la récupération des données : ' . $exception->getMessage());
}

// ============================================
// MODE MODIFICATION : Récupération des données
// ============================================

if (isset($_GET['modifier']) && is_numeric($_GET['modifier'])) {
    $modeModification = true;
    $numeroActivite = (int) $_GET['modifier'];
    
    try {
        $requete = $pdo->prepare('SELECT * FROM ACTIVITE WHERE Num_Activite = :numero');
        $requete->execute(['numero' => $numeroActivite]);
        $activite = $requete->fetch();
        
        if ($activite) {
            $donneesFormulaire = [
                'libelle' => $activite['Libelle'],
                'description' => $activite['Description'],
                'jourSemaine' => $activite['Jour_Semaine'],
                'horaire' => $activite['Horaire'],
                'dureeSeance' => $activite['Duree_Seance'],
                'tarifAnnuel' => $activite['Tarif_Annuel'],
                'codeSection' => $activite['Code_Section'],
                'idLieu' => $activite['Id_Lieu']
            ];
        } else {
            $messageAlerte = afficherAlerte('Activité non trouvée.', 'danger');
            $modeModification = false;
        }
    } catch (PDOException $exception) {
        $messageAlerte = afficherAlerte('Erreur : ' . $exception->getMessage(), 'danger');
    }
}

// Titre de la page
$titrePage = $modeModification ? 'Modifier une activité' : 'Ajouter une activité';

// ============================================
// TRAITEMENT DU FORMULAIRE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération et nettoyage des données
    $donneesFormulaire = [
        'libelle' => trim($_POST['libelle'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'jourSemaine' => trim($_POST['jourSemaine'] ?? ''),
        'horaire' => trim($_POST['horaire'] ?? ''),
        'dureeSeance' => trim($_POST['dureeSeance'] ?? ''),
        'tarifAnnuel' => trim($_POST['tarifAnnuel'] ?? ''),
        'codeSection' => trim($_POST['codeSection'] ?? ''),
        'idLieu' => trim($_POST['idLieu'] ?? '')
    ];
    
    if (isset($_POST['numeroActivite']) && is_numeric($_POST['numeroActivite'])) {
        $modeModification = true;
        $numeroActivite = (int) $_POST['numeroActivite'];
    }
    
    // ============================================
    // VALIDATION DES DONNÉES
    // ============================================
    
    if (empty($donneesFormulaire['libelle'])) {
        $erreurs['libelle'] = 'Le libellé est obligatoire.';
    } elseif (strlen($donneesFormulaire['libelle']) > 100) {
        $erreurs['libelle'] = 'Le libellé ne doit pas dépasser 100 caractères.';
    }
    
    if (empty($donneesFormulaire['jourSemaine'])) {
        $erreurs['jourSemaine'] = 'Le jour de la semaine est obligatoire.';
    }
    
    if (empty($donneesFormulaire['horaire'])) {
        $erreurs['horaire'] = 'L\'horaire est obligatoire.';
    }
    
    if (!empty($donneesFormulaire['dureeSeance']) && !is_numeric($donneesFormulaire['dureeSeance'])) {
        $erreurs['dureeSeance'] = 'La durée doit être un nombre.';
    }
    
    if (!empty($donneesFormulaire['tarifAnnuel']) && !is_numeric($donneesFormulaire['tarifAnnuel'])) {
        $erreurs['tarifAnnuel'] = 'Le tarif doit être un nombre.';
    }
    
    // ============================================
    // ENREGISTREMENT SI PAS D'ERREURS
    // ============================================
    
    if (empty($erreurs)) {
        try {
            // Préparation des valeurs NULL
            $codeSection = !empty($donneesFormulaire['codeSection']) ? $donneesFormulaire['codeSection'] : null;
            $idLieu = !empty($donneesFormulaire['idLieu']) ? (int)$donneesFormulaire['idLieu'] : null;
            $dureeSeance = !empty($donneesFormulaire['dureeSeance']) ? (int)$donneesFormulaire['dureeSeance'] : null;
            $tarifAnnuel = !empty($donneesFormulaire['tarifAnnuel']) ? (float)$donneesFormulaire['tarifAnnuel'] : null;
            
            if ($modeModification) {
                $requete = $pdo->prepare('
                    UPDATE ACTIVITE 
                    SET Libelle = :libelle, 
                        Description = :description, 
                        Jour_Semaine = :jourSemaine, 
                        Horaire = :horaire, 
                        Duree_Seance = :dureeSeance, 
                        Tarif_Annuel = :tarifAnnuel,
                        Code_Section = :codeSection,
                        Id_Lieu = :idLieu
                    WHERE Num_Activite = :numero
                ');
                $requete->execute([
                    'libelle' => $donneesFormulaire['libelle'],
                    'description' => $donneesFormulaire['description'],
                    'jourSemaine' => $donneesFormulaire['jourSemaine'],
                    'horaire' => $donneesFormulaire['horaire'],
                    'dureeSeance' => $dureeSeance,
                    'tarifAnnuel' => $tarifAnnuel,
                    'codeSection' => $codeSection,
                    'idLieu' => $idLieu,
                    'numero' => $numeroActivite
                ]);
                
                $messageAlerte = afficherAlerte('Activité modifiée avec succès.', 'success');
                
            } else {
                $requete = $pdo->prepare('
                    INSERT INTO ACTIVITE (Libelle, Description, Jour_Semaine, Horaire, Duree_Seance, Tarif_Annuel, Code_Section, Id_Lieu)
                    VALUES (:libelle, :description, :jourSemaine, :horaire, :dureeSeance, :tarifAnnuel, :codeSection, :idLieu)
                ');
                $requete->execute([
                    'libelle' => $donneesFormulaire['libelle'],
                    'description' => $donneesFormulaire['description'],
                    'jourSemaine' => $donneesFormulaire['jourSemaine'],
                    'horaire' => $donneesFormulaire['horaire'],
                    'dureeSeance' => $dureeSeance,
                    'tarifAnnuel' => $tarifAnnuel,
                    'codeSection' => $codeSection,
                    'idLieu' => $idLieu
                ]);
                
                $messageAlerte = afficherAlerte('Activité ajoutée avec succès.', 'success');
                
                // Réinitialisation du formulaire
                $donneesFormulaire = [
                    'libelle' => '',
                    'description' => '',
                    'jourSemaine' => '',
                    'horaire' => '',
                    'dureeSeance' => '',
                    'tarifAnnuel' => '',
                    'codeSection' => '',
                    'idLieu' => ''
                ];
            }
            
        } catch (PDOException $exception) {
            $messageAlerte = afficherAlerte('Erreur lors de l\'enregistrement : ' . $exception->getMessage(), 'danger');
        }
    } else {
        $messageAlerte = afficherAlerte('Veuillez corriger les erreurs ci-dessous.', 'danger');
    }
}

require_once 'header.php';
?>

<!-- Titre de la page -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="bi bi-<?php echo $modeModification ? 'pencil' : 'plus-circle'; ?>"></i> 
        <?php echo echapper($titrePage); ?>
    </h1>
    <a href="activites.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Retour à la liste
    </a>
</div>

<?php echo $messageAlerte; ?>

<!-- Formulaire -->
<div class="card">
    <div class="card-header bg-success text-white">
        <i class="bi bi-calendar-event"></i> Informations de l'activité
    </div>
    <div class="card-body">
        <form method="post" action="activite_formulaire.php" novalidate>
            
            <?php if ($modeModification): ?>
                <input type="hidden" name="numeroActivite" value="<?php echo $numeroActivite; ?>">
            <?php endif; ?>
            
            <div class="row">
                <!-- Libellé -->
                <div class="col-md-6 mb-3">
                    <label for="libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control <?php echo isset($erreurs['libelle']) ? 'is-invalid' : ''; ?>" 
                           id="libelle" 
                           name="libelle" 
                           value="<?php echo echapper($donneesFormulaire['libelle']); ?>" 
                           required 
                           maxlength="100">
                    <?php if (isset($erreurs['libelle'])): ?>
                        <div class="invalid-feedback"><?php echo echapper($erreurs['libelle']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Section -->
                <div class="col-md-6 mb-3">
                    <label for="codeSection" class="form-label">Section</label>
                    <select class="form-select" id="codeSection" name="codeSection">
                        <option value="">-- Sélectionnez une section --</option>
                        <?php foreach ($listeSections as $section): ?>
                            <option value="<?php echo echapper($section['Code_Section']); ?>"
                                    <?php echo $donneesFormulaire['codeSection'] === $section['Code_Section'] ? 'selected' : ''; ?>>
                                <?php echo echapper($section['Libelle']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Description -->
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" 
                          id="description" 
                          name="description" 
                          rows="3"><?php echo echapper($donneesFormulaire['description']); ?></textarea>
            </div>
            
            <div class="row">
                <!-- Jour de la semaine -->
                <div class="col-md-3 mb-3">
                    <label for="jourSemaine" class="form-label">Jour <span class="text-danger">*</span></label>
                    <select class="form-select <?php echo isset($erreurs['jourSemaine']) ? 'is-invalid' : ''; ?>" 
                            id="jourSemaine" 
                            name="jourSemaine" 
                            required>
                        <option value="">-- Jour --</option>
                        <?php foreach ($joursSemaine as $jour): ?>
                            <option value="<?php echo $jour; ?>"
                                    <?php echo $donneesFormulaire['jourSemaine'] === $jour ? 'selected' : ''; ?>>
                                <?php echo $jour; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erreurs['jourSemaine'])): ?>
                        <div class="invalid-feedback"><?php echo echapper($erreurs['jourSemaine']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Horaire -->
                <div class="col-md-3 mb-3">
                    <label for="horaire" class="form-label">Horaire <span class="text-danger">*</span></label>
                    <input type="time" 
                           class="form-control <?php echo isset($erreurs['horaire']) ? 'is-invalid' : ''; ?>" 
                           id="horaire" 
                           name="horaire" 
                           value="<?php echo echapper($donneesFormulaire['horaire']); ?>" 
                           required>
                    <?php if (isset($erreurs['horaire'])): ?>
                        <div class="invalid-feedback"><?php echo echapper($erreurs['horaire']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Durée -->
                <div class="col-md-3 mb-3">
                    <label for="dureeSeance" class="form-label">Durée (min)</label>
                    <input type="number" 
                           class="form-control <?php echo isset($erreurs['dureeSeance']) ? 'is-invalid' : ''; ?>" 
                           id="dureeSeance" 
                           name="dureeSeance" 
                           value="<?php echo echapper($donneesFormulaire['dureeSeance']); ?>" 
                           min="0">
                    <?php if (isset($erreurs['dureeSeance'])): ?>
                        <div class="invalid-feedback"><?php echo echapper($erreurs['dureeSeance']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Tarif annuel -->
                <div class="col-md-3 mb-3">
                    <label for="tarifAnnuel" class="form-label">Tarif annuel (€)</label>
                    <input type="number" 
                           class="form-control <?php echo isset($erreurs['tarifAnnuel']) ? 'is-invalid' : ''; ?>" 
                           id="tarifAnnuel" 
                           name="tarifAnnuel" 
                           value="<?php echo echapper($donneesFormulaire['tarifAnnuel']); ?>" 
                           min="0" 
                           step="0.01">
                    <?php if (isset($erreurs['tarifAnnuel'])): ?>
                        <div class="invalid-feedback"><?php echo echapper($erreurs['tarifAnnuel']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Lieu -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="idLieu" class="form-label">Lieu</label>
                    <select class="form-select" id="idLieu" name="idLieu">
                        <option value="">-- Sélectionnez un lieu --</option>
                        <?php foreach ($listeLieux as $lieu): ?>
                            <option value="<?php echo $lieu['Id_Lieu']; ?>"
                                    <?php echo $donneesFormulaire['idLieu'] == $lieu['Id_Lieu'] ? 'selected' : ''; ?>>
                                <?php echo echapper($lieu['Nom']); ?> (capacité: <?php echo $lieu['Capacite']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <hr>
            
            <div class="d-flex justify-content-between">
                <a href="activites.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Annuler
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> 
                    <?php echo $modeModification ? 'Modifier' : 'Enregistrer'; ?>
                </button>
            </div>
            
        </form>
    </div>
</div>

<p class="text-muted mt-3">
    <small><span class="text-danger">*</span> Champs obligatoires</small>
</p>

<?php
require_once 'footer.php';
?>
