<?php
/**
 * Formulaire d'ajout/modification d'une section
 * Association Sportive - Projet ASCG
 */

// Inclusion de la configuration
require_once 'config.php';

// ============================================
// VARIABLES DU FORMULAIRE
// ============================================

$modeModification = false;
$ancienCodeSection = null;
$messageAlerte = '';
$erreurs = [];

// Valeurs par défaut du formulaire
$donneesFormulaire = [
    'codeSection' => '',
    'libelle' => '',
    'debutSaison' => '',
    'idBenevole' => ''
];

// ============================================
// RÉCUPÉRATION DES BÉNÉVOLES POUR LE SELECT
// ============================================

try {
    $requeteBenevoles = $pdo->query('SELECT Id_Benevole, Nom, Prenom FROM BENEVOLE ORDER BY Nom, Prenom');
    $listeBenevoles = $requeteBenevoles->fetchAll();
} catch (PDOException $exception) {
    die('Erreur lors de la récupération des bénévoles : ' . $exception->getMessage());
}

// ============================================
// MODE MODIFICATION : Récupération des données
// ============================================

if (isset($_GET['modifier']) && !empty($_GET['modifier'])) {
    $modeModification = true;
    $ancienCodeSection = trim($_GET['modifier']);
    
    try {
        $requete = $pdo->prepare('SELECT * FROM SECTION WHERE Code_Section = :code');
        $requete->execute(['code' => $ancienCodeSection]);
        $section = $requete->fetch();
        
        if ($section) {
            $donneesFormulaire = [
                'codeSection' => $section['Code_Section'],
                'libelle' => $section['Libelle'],
                'debutSaison' => $section['Debut_Saison'],
                'idBenevole' => $section['Id_Benevole']
            ];
        } else {
            $messageAlerte = afficherAlerte('Section non trouvée.', 'danger');
            $modeModification = false;
        }
    } catch (PDOException $exception) {
        $messageAlerte = afficherAlerte('Erreur : ' . $exception->getMessage(), 'danger');
    }
}

// Titre de la page
$titrePage = $modeModification ? 'Modifier une section' : 'Ajouter une section';

// ============================================
// TRAITEMENT DU FORMULAIRE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération et nettoyage des données
    $donneesFormulaire = [
        'codeSection' => strtoupper(trim($_POST['codeSection'] ?? '')),
        'libelle' => trim($_POST['libelle'] ?? ''),
        'debutSaison' => trim($_POST['debutSaison'] ?? ''),
        'idBenevole' => trim($_POST['idBenevole'] ?? '')
    ];
    
    if (isset($_POST['ancienCodeSection']) && !empty($_POST['ancienCodeSection'])) {
        $modeModification = true;
        $ancienCodeSection = trim($_POST['ancienCodeSection']);
    }
    
    // ============================================
    // VALIDATION DES DONNÉES
    // ============================================
    
    if (empty($donneesFormulaire['codeSection'])) {
        $erreurs['codeSection'] = 'Le code section est obligatoire.';
    } elseif (strlen($donneesFormulaire['codeSection']) > 10) {
        $erreurs['codeSection'] = 'Le code section ne doit pas dépasser 10 caractères.';
    } elseif (!preg_match('/^[A-Z0-9_]+$/', $donneesFormulaire['codeSection'])) {
        $erreurs['codeSection'] = 'Le code section ne doit contenir que des lettres majuscules, chiffres ou underscore.';
    }
    
    if (empty($donneesFormulaire['libelle'])) {
        $erreurs['libelle'] = 'Le libellé est obligatoire.';
    } elseif (strlen($donneesFormulaire['libelle']) > 100) {
        $erreurs['libelle'] = 'Le libellé ne doit pas dépasser 100 caractères.';
    }
    
    // Vérifier que le code n'existe pas déjà (sauf en modification avec le même code)
    if (empty($erreurs['codeSection'])) {
        try {
            $requeteVerif = $pdo->prepare('SELECT COUNT(*) as existe FROM SECTION WHERE Code_Section = :code');
            $requeteVerif->execute(['code' => $donneesFormulaire['codeSection']]);
            $resultat = $requeteVerif->fetch();
            
            if ($resultat['existe'] > 0 && (!$modeModification || $donneesFormulaire['codeSection'] !== $ancienCodeSection)) {
                $erreurs['codeSection'] = 'Ce code section existe déjà.';
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
            $idBenevole = !empty($donneesFormulaire['idBenevole']) ? (int)$donneesFormulaire['idBenevole'] : null;
            $debutSaison = !empty($donneesFormulaire['debutSaison']) ? $donneesFormulaire['debutSaison'] : null;
            
            if ($modeModification) {
                // Si le code a changé, on doit supprimer l'ancien et créer le nouveau
                if ($donneesFormulaire['codeSection'] !== $ancienCodeSection) {
                    // Vérifier qu'il n'y a pas d'activités liées
                    $requeteVerif = $pdo->prepare('SELECT COUNT(*) as total FROM ACTIVITE WHERE Code_Section = :code');
                    $requeteVerif->execute(['code' => $ancienCodeSection]);
                    $resultat = $requeteVerif->fetch();
                    
                    if ($resultat['total'] > 0) {
                        throw new Exception('Impossible de modifier le code : des activités sont liées à cette section.');
                    }
                    
                    // Supprimer l'ancienne et créer la nouvelle
                    $pdo->prepare('DELETE FROM SECTION WHERE Code_Section = :code')->execute(['code' => $ancienCodeSection]);
                    
                    $requete = $pdo->prepare('
                        INSERT INTO SECTION (Code_Section, Libelle, Debut_Saison, Id_Benevole)
                        VALUES (:codeSection, :libelle, :debutSaison, :idBenevole)
                    ');
                    $requete->execute([
                        'codeSection' => $donneesFormulaire['codeSection'],
                        'libelle' => $donneesFormulaire['libelle'],
                        'debutSaison' => $debutSaison,
                        'idBenevole' => $idBenevole
                    ]);
                } else {
                    $requete = $pdo->prepare('
                        UPDATE SECTION 
                        SET Libelle = :libelle, 
                            Debut_Saison = :debutSaison, 
                            Id_Benevole = :idBenevole
                        WHERE Code_Section = :codeSection
                    ');
                    $requete->execute([
                        'libelle' => $donneesFormulaire['libelle'],
                        'debutSaison' => $debutSaison,
                        'idBenevole' => $idBenevole,
                        'codeSection' => $donneesFormulaire['codeSection']
                    ]);
                }
                
                $messageAlerte = afficherAlerte('Section modifiée avec succès.', 'success');
                $ancienCodeSection = $donneesFormulaire['codeSection'];
                
            } else {
                $requete = $pdo->prepare('
                    INSERT INTO SECTION (Code_Section, Libelle, Debut_Saison, Id_Benevole)
                    VALUES (:codeSection, :libelle, :debutSaison, :idBenevole)
                ');
                $requete->execute([
                    'codeSection' => $donneesFormulaire['codeSection'],
                    'libelle' => $donneesFormulaire['libelle'],
                    'debutSaison' => $debutSaison,
                    'idBenevole' => $idBenevole
                ]);
                
                $messageAlerte = afficherAlerte('Section ajoutée avec succès.', 'success');
                
                // Réinitialisation du formulaire
                $donneesFormulaire = [
                    'codeSection' => '',
                    'libelle' => '',
                    'debutSaison' => '',
                    'idBenevole' => ''
                ];
            }
            
        } catch (Exception $exception) {
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
    <a href="sections.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Retour à la liste
    </a>
</div>

<?php echo $messageAlerte; ?>

<!-- Formulaire -->
<div class="card">
    <div class="card-header bg-warning">
        <i class="bi bi-diagram-3"></i> Informations de la section
    </div>
    <div class="card-body">
        <form method="post" action="section_formulaire.php" novalidate>
            
            <?php if ($modeModification): ?>
                <input type="hidden" name="ancienCodeSection" value="<?php echo echapper($ancienCodeSection); ?>">
            <?php endif; ?>
            
            <div class="row">
                <!-- Code section -->
                <div class="col-md-4 mb-3">
                    <label for="codeSection" class="form-label">Code Section <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control <?php echo isset($erreurs['codeSection']) ? 'is-invalid' : ''; ?>" 
                           id="codeSection" 
                           name="codeSection" 
                           value="<?php echo echapper($donneesFormulaire['codeSection']); ?>" 
                           required 
                           maxlength="10"
                           style="text-transform: uppercase;"
                           placeholder="Ex: FOOT, BASKET"
                           <?php echo $modeModification ? 'readonly' : ''; ?>>
                    <?php if (isset($erreurs['codeSection'])): ?>
                        <div class="invalid-feedback"><?php echo echapper($erreurs['codeSection']); ?></div>
                    <?php endif; ?>
                    <div class="form-text">Lettres majuscules, chiffres ou underscore uniquement</div>
                </div>
                
                <!-- Libellé -->
                <div class="col-md-8 mb-3">
                    <label for="libelle" class="form-label">Libellé <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control <?php echo isset($erreurs['libelle']) ? 'is-invalid' : ''; ?>" 
                           id="libelle" 
                           name="libelle" 
                           value="<?php echo echapper($donneesFormulaire['libelle']); ?>" 
                           required 
                           maxlength="100"
                           placeholder="Ex: Football, Basketball">
                    <?php if (isset($erreurs['libelle'])): ?>
                        <div class="invalid-feedback"><?php echo echapper($erreurs['libelle']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="row">
                <!-- Début de saison -->
                <div class="col-md-4 mb-3">
                    <label for="debutSaison" class="form-label">Début de saison</label>
                    <input type="date" 
                           class="form-control" 
                           id="debutSaison" 
                           name="debutSaison" 
                           value="<?php echo echapper($donneesFormulaire['debutSaison']); ?>">
                </div>
                
                <!-- Responsable (bénévole) -->
                <div class="col-md-8 mb-3">
                    <label for="idBenevole" class="form-label">Responsable</label>
                    <select class="form-select" id="idBenevole" name="idBenevole">
                        <option value="">-- Sélectionnez un responsable --</option>
                        <?php foreach ($listeBenevoles as $benevole): ?>
                            <option value="<?php echo $benevole['Id_Benevole']; ?>"
                                    <?php echo $donneesFormulaire['idBenevole'] == $benevole['Id_Benevole'] ? 'selected' : ''; ?>>
                                <?php echo echapper($benevole['Prenom'] . ' ' . $benevole['Nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <hr>
            
            <div class="d-flex justify-content-between">
                <a href="sections.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Annuler
                </a>
                <button type="submit" class="btn btn-warning">
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
