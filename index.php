<?php
/**
 * Page d'accueil
 * Association Sportive - Projet ASCG
 */

// Titre de la page
$titrePage = 'Accueil';

// Inclusion de la configuration
require_once 'config.php';

// ============================================
// RÉCUPÉRATION DES STATISTIQUES
// ============================================

try {
    // Nombre d'adhérents
    $requete = $pdo->query('SELECT COUNT(*) as total FROM ADHERENT');
    $nombreAdherents = $requete->fetch()['total'];
    
    // Nombre d'activités
    $requete = $pdo->query('SELECT COUNT(*) as total FROM ACTIVITE');
    $nombreActivites = $requete->fetch()['total'];
    
    // Nombre de sections
    $requete = $pdo->query('SELECT COUNT(*) as total FROM SECTION');
    $nombreSections = $requete->fetch()['total'];
    
    // Nombre d'inscriptions
    $requete = $pdo->query('SELECT COUNT(*) as total FROM ADHESION');
    $nombreInscriptions = $requete->fetch()['total'];
    
} catch (PDOException $exception) {
    $nombreAdherents = 0;
    $nombreActivites = 0;
    $nombreSections = 0;
    $nombreInscriptions = 0;
}

// Inclusion de l'en-tête
require_once 'header.php';
?>

<!-- Bannière d'accueil -->
<div class="p-5 mb-4 bg-primary text-white rounded-3">
    <div class="container-fluid py-5">
        <h1 class="display-5 fw-bold">
            <i class="bi bi-trophy"></i> Bienvenue à l'Association Sportive
        </h1>
        <p class="col-md-8 fs-4">
            Gérez facilement vos adhérents, activités et inscriptions avec notre application de gestion.
        </p>
        <a href="adherents.php" class="btn btn-light btn-lg">
            <i class="bi bi-people"></i> Voir les adhérents
        </a>
    </div>
</div>

<!-- Cartes de statistiques -->
<div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
    
    <!-- Adhérents -->
    <div class="col">
        <div class="card text-white bg-primary h-100">
            <div class="card-body text-center">
                <i class="bi bi-people display-4"></i>
                <h2 class="card-title display-5"><?php echo $nombreAdherents; ?></h2>
                <p class="card-text">Adhérents</p>
            </div>
            <div class="card-footer bg-transparent border-0 text-center">
                <a href="adherents.php" class="btn btn-outline-light btn-sm">
                    Voir la liste
                </a>
            </div>
        </div>
    </div>
    
    <!-- Activités -->
    <div class="col">
        <div class="card text-white bg-success h-100">
            <div class="card-body text-center">
                <i class="bi bi-calendar-event display-4"></i>
                <h2 class="card-title display-5"><?php echo $nombreActivites; ?></h2>
                <p class="card-text">Activités</p>
            </div>
            <div class="card-footer bg-transparent border-0 text-center">
                <a href="activites.php" class="btn btn-outline-light btn-sm">
                    Voir les activités
                </a>
            </div>
        </div>
    </div>
    
    <!-- Sections -->
    <div class="col">
        <div class="card text-white bg-warning h-100">
            <div class="card-body text-center">
                <i class="bi bi-diagram-3 display-4"></i>
                <h2 class="card-title display-5"><?php echo $nombreSections; ?></h2>
                <p class="card-text">Sections</p>
            </div>
            <div class="card-footer bg-transparent border-0 text-center">
                <a href="sections.php" class="btn btn-outline-light btn-sm">
                    Voir les sections
                </a>
            </div>
        </div>
    </div>
    
    <!-- Inscriptions -->
    <div class="col">
        <div class="card text-white bg-danger h-100">
            <div class="card-body text-center">
                <i class="bi bi-card-checklist display-4"></i>
                <h2 class="card-title display-5"><?php echo $nombreInscriptions; ?></h2>
                <p class="card-text">Inscriptions</p>
            </div>
            <div class="card-footer bg-transparent border-0 text-center">
                <a href="inscriptions.php" class="btn btn-outline-light btn-sm">
                    Voir les inscriptions
                </a>
            </div>
        </div>
    </div>
    
</div>

<!-- Actions rapides -->
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <i class="bi bi-lightning"></i> Actions rapides
            </div>
            <div class="list-group list-group-flush">
                <a href="adherent_formulaire.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-person-plus text-success"></i> Ajouter un nouvel adhérent
                </a>
                <a href="inscriptions.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-plus-circle text-primary"></i> Nouvelle inscription
                </a>
                <a href="adherents.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-search text-info"></i> Rechercher un adhérent
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <i class="bi bi-info-circle"></i> Informations
            </div>
            <div class="card-body">
                <p class="card-text">
                    <strong>Saison actuelle :</strong> 2025-2026
                </p>
                <p class="card-text">
                    <strong>Date :</strong> <?php echo date('d/m/Y'); ?>
                </p>
                <p class="card-text text-muted">
                    Application développée en PHP natif avec PDO et Bootstrap 5.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
// Inclusion du pied de page
require_once 'footer.php';
?>
