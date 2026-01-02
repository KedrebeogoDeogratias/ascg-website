<?php
/**
 * Formulaire d'ajout/modification d'un adhérent
 * Association Sportive - Projet ASCG
 */

// Inclusion de la configuration
require_once 'config.php';

// ============================================
// VARIABLES DU FORMULAIRE
// ============================================

$modeModification = false;
$numeroAdherent = null;
$messageAlerte = '';
$erreurs = [];

// Valeurs par défaut du formulaire
$donneesFormulaire = [
    'nom' => '',
    'prenom' => '',
    'adresse' => '',
    'codePostal' => '',
    'ville' => '',
    'telephone' => '',
    'courriel' => '',
    'dateNaissance' => ''
];

// ============================================
// MODE MODIFICATION : Récupération des données
// ============================================

if (isset($_GET['modifier']) && is_numeric($_GET['modifier'])) {
    $modeModification = true;
    $numeroAdherent = (int) $_GET['modifier'];
    
    try {
        $requete = $pdo->prepare('SELECT * FROM ADHERENT WHERE Num_Adherent = :numero');
        $requete->execute(['numero' => $numeroAdherent]);
        $adherent = $requete->fetch();
        
        if ($adherent) {
            $donneesFormulaire = [
                'nom' => $adherent['Nom'],
                'prenom' => $adherent['Prenom'],
                'adresse' => $adherent['Adresse'],
                'codePostal' => $adherent['Code_Postal'],
                'ville' => $adherent['Ville'],
                'telephone' => $adherent['Telephone'],
                'courriel' => $adherent['Courriel'],
                'dateNaissance' => $adherent['Date_Naissance']
            ];
        } else {
            $messageAlerte = afficherAlerte('Adhérent non trouvé.', 'danger');
            $modeModification = false;
        }
    } catch (PDOException $exception) {
        $messageAlerte = afficherAlerte('Erreur : ' . $exception->getMessage(), 'danger');
    }
}

// Titre de la page
$titrePage = $modeModification ? 'Modifier un adhérent' : 'Ajouter un adhérent';

// ============================================
// TRAITEMENT DU FORMULAIRE
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération et nettoyage des données
    $donneesFormulaire = [
        'nom' => trim($_POST['nom'] ?? ''),
        'prenom' => trim($_POST['prenom'] ?? ''),
        'adresse' => trim($_POST['adresse'] ?? ''),
        'codePostal' => trim($_POST['codePostal'] ?? ''),
        'ville' => trim($_POST['ville'] ?? ''),
        'telephone' => trim($_POST['telephone'] ?? ''),
        'courriel' => trim($_POST['courriel'] ?? ''),
        'dateNaissance' => trim($_POST['dateNaissance'] ?? '')
    ];
    
    // Récupération du numéro en cas de modification
    if (isset($_POST['numeroAdherent']) && is_numeric($_POST['numeroAdherent'])) {
        $modeModification = true;
        $numeroAdherent = (int) $_POST['numeroAdherent'];
    }
    
    // ============================================
    // VALIDATION DES DONNÉES
    // ============================================
    
    // Nom obligatoire
    if (empty($donneesFormulaire['nom'])) {
        $erreurs['nom'] = 'Le nom est obligatoire.';
    } elseif (strlen($donneesFormulaire['nom']) > 50) {
        $erreurs['nom'] = 'Le nom ne doit pas dépasser 50 caractères.';
    }
    
    // Prénom obligatoire
    if (empty($donneesFormulaire['prenom'])) {
        $erreurs['prenom'] = 'Le prénom est obligatoire.';
    } elseif (strlen($donneesFormulaire['prenom']) > 50) {
        $erreurs['prenom'] = 'Le prénom ne doit pas dépasser 50 caractères.';
    }
    
    // Code postal (format français)
    if (!empty($donneesFormulaire['codePostal']) && !preg_match('/^[0-9]{5}$/', $donneesFormulaire['codePostal'])) {
        $erreurs['codePostal'] = 'Le code postal doit contenir 5 chiffres.';
    }
    
    // Courriel valide
    if (!empty($donneesFormulaire['courriel']) && !filter_var($donneesFormulaire['courriel'], FILTER_VALIDATE_EMAIL)) {
        $erreurs['courriel'] = 'L\'adresse courriel n\'est pas valide.';
    }
    
    // Téléphone (format français)
    if (!empty($donneesFormulaire['telephone']) && !preg_match('/^[0-9]{10}$/', $donneesFormulaire['telephone'])) {
        $erreurs['telephone'] = 'Le téléphone doit contenir 10 chiffres.';
    }
    
    // Date de naissance valide
    if (!empty($donneesFormulaire['dateNaissance'])) {
        $dateTest = DateTime::createFromFormat('Y-m-d', $donneesFormulaire['dateNaissance']);
        if (!$dateTest || $dateTest > new DateTime()) {
            $erreurs['dateNaissance'] = 'La date de naissance n\'est pas valide.';
        }
    }
    
    // ============================================
    // ENREGISTREMENT SI PAS D'ERREURS
    // ============================================
    
    if (empty($erreurs)) {
        try {
            // Préparation de la date (NULL si vide)
            $dateNaissance = !empty($donneesFormulaire['dateNaissance']) ? $donneesFormulaire['dateNaissance'] : null;
            
            if ($modeModification) {
                // Requête de mise à jour
                $requete = $pdo->prepare('
                    UPDATE ADHERENT 
                    SET Nom = :nom, 
                        Prenom = :prenom, 
                        Adresse = :adresse, 
                        Code_Postal = :codePostal, 
                        Ville = :ville, 
                        Telephone = :telephone, 
                        Courriel = :courriel, 
                        Date_Naissance = :dateNaissance
                    WHERE Num_Adherent = :numero
                ');
                $requete->execute([
                    'nom' => $donneesFormulaire['nom'],
                    'prenom' => $donneesFormulaire['prenom'],
                    'adresse' => $donneesFormulaire['adresse'],
                    'codePostal' => $donneesFormulaire['codePostal'],
                    'ville' => $donneesFormulaire['ville'],
                    'telephone' => $donneesFormulaire['telephone'],
                    'courriel' => $donneesFormulaire['courriel'],
                    'dateNaissance' => $dateNaissance,
                    'numero' => $numeroAdherent
                ]);
                
                $messageAlerte = afficherAlerte('Adhérent modifié avec succès.', 'success');
                
            } else {
                // Requête d'insertion
                $requete = $pdo->prepare('
                    INSERT INTO ADHERENT (Nom, Prenom, Adresse, Code_Postal, Ville, Telephone, Courriel, Date_Naissance)
                    VALUES (:nom, :prenom, :adresse, :codePostal, :ville, :telephone, :courriel, :dateNaissance)
                ');
                $requete->execute([
                    'nom' => $donneesFormulaire['nom'],
                    'prenom' => $donneesFormulaire['prenom'],
                    'adresse' => $donneesFormulaire['adresse'],
                    'codePostal' => $donneesFormulaire['codePostal'],
                    'ville' => $donneesFormulaire['ville'],
                    'telephone' => $donneesFormulaire['telephone'],
                    'courriel' => $donneesFormulaire['courriel'],
                    'dateNaissance' => $dateNaissance
                ]);
                
                $messageAlerte = afficherAlerte('Adhérent ajouté avec succès.', 'success');
                
                // Réinitialisation du formulaire après ajout
                $donneesFormulaire = [
                    'nom' => '',
                    'prenom' => '',
                    'adresse' => '',
                    'codePostal' => '',
                    'ville' => '',
                    'telephone' => '',
                    'courriel' => '',
                    'dateNaissance' => ''
                ];
            }
            
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
        <i class="bi bi-<?php echo $modeModification ? 'pencil' : 'person-plus'; ?>"></i> 
        <?php echo echapper($titrePage); ?>
    </h1>
    <a href="adherents.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Retour à la liste
    </a>
</div>

<!-- Message d'alerte -->
<?php echo $messageAlerte; ?>

<!-- Formulaire -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-person"></i> Informations de l'adhérent
    </div>
    <div class="card-body">
        <form method="post" action="adherent_formulaire.php" novalidate>
            
            <!-- Champ caché pour le mode modification -->
            <?php if ($modeModification): ?>
                <input type="hidden" name="numeroAdherent" value="<?php echo $numeroAdherent; ?>">
            <?php endif; ?>
            
            <div class="row">
                <!-- Nom -->
                <div class="col-md-6 mb-3">
                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control <?php echo isset($erreurs['nom']) ? 'is-invalid' : ''; ?>" 
                           id="nom" 
                           name="nom" 
                           value="<?php echo echapper($donneesFormulaire['nom']); ?>" 
                           required 
                           maxlength="50">
                    <?php if (isset($erreurs['nom'])): ?>
                        <div class="invalid-feedback"><?php echo echapper($erreurs['nom']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Prénom -->
                <div class="col-md-6 mb-3">
                    <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control <?php echo isset($erreurs['prenom']) ? 'is-invalid' : ''; ?>" 
                           id="prenom" 
                           name="prenom" 
                           value="<?php echo echapper($donneesFormulaire['prenom']); ?>" 
                           required 
                           maxlength="50">
                    <?php if (isset($erreurs['prenom'])): ?>
                        <div class="invalid-feedback"><?php echo echapper($erreurs['prenom']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Adresse -->
            <div class="mb-3">
                <label for="adresse" class="form-label">Adresse</label>
                <input type="text" 
                       class="form-control" 
                       id="adresse" 
                       name="adresse" 
                       value="<?php echo echapper($donneesFormulaire['adresse']); ?>" 
                       maxlength="255">
            </div>
            
            <div class="row">
                <!-- Code postal -->
                <div class="col-md-4 mb-3">
                    <label for="codePostal" class="form-label">Code postal</label>
                    <input type="text" 
                           class="form-control <?php echo isset($erreurs['codePostal']) ? 'is-invalid' : ''; ?>" 
                           id="codePostal" 
                           name="codePostal" 
                           value="<?php echo echapper($donneesFormulaire['codePostal']); ?>" 
                           maxlength="5" 
                           pattern="[0-9]{5}">
                    <?php if (isset($erreurs['codePostal'])): ?>
                        <div class="invalid-feedback"><?php echo echapper($erreurs['codePostal']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Ville -->
                <div class="col-md-8 mb-3">
                    <label for="ville" class="form-label">Ville</label>
                    <input type="text" 
                           class="form-control" 
                           id="ville" 
                           name="ville" 
                           value="<?php echo echapper($donneesFormulaire['ville']); ?>" 
                           maxlength="100">
                </div>
            </div>
            
            <div class="row">
                <!-- Téléphone -->
                <div class="col-md-6 mb-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" 
                           class="form-control <?php echo isset($erreurs['telephone']) ? 'is-invalid' : ''; ?>" 
                           id="telephone" 
                           name="telephone" 
                           value="<?php echo echapper($donneesFormulaire['telephone']); ?>" 
                           maxlength="10" 
                           pattern="[0-9]{10}" 
                           placeholder="0612345678">
                    <?php if (isset($erreurs['telephone'])): ?>
                        <div class="invalid-feedback"><?php echo echapper($erreurs['telephone']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Courriel -->
                <div class="col-md-6 mb-3">
                    <label for="courriel" class="form-label">Courriel</label>
                    <input type="email" 
                           class="form-control <?php echo isset($erreurs['courriel']) ? 'is-invalid' : ''; ?>" 
                           id="courriel" 
                           name="courriel" 
                           value="<?php echo echapper($donneesFormulaire['courriel']); ?>" 
                           maxlength="100">
                    <?php if (isset($erreurs['courriel'])): ?>
                        <div class="invalid-feedback"><?php echo echapper($erreurs['courriel']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Date de naissance -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="dateNaissance" class="form-label">Date de naissance</label>
                    <input type="date" 
                           class="form-control <?php echo isset($erreurs['dateNaissance']) ? 'is-invalid' : ''; ?>" 
                           id="dateNaissance" 
                           name="dateNaissance" 
                           value="<?php echo echapper($donneesFormulaire['dateNaissance']); ?>">
                    <?php if (isset($erreurs['dateNaissance'])): ?>
                        <div class="invalid-feedback"><?php echo echapper($erreurs['dateNaissance']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <hr>
            
            <!-- Boutons de soumission -->
            <div class="d-flex justify-content-between">
                <a href="adherents.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Annuler
                </a>
                <button type="submit" class="btn btn-primary">
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
// Inclusion du pied de page
require_once 'footer.php';
?>
