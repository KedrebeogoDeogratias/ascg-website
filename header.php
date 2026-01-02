<?php
/**
 * En-tête HTML avec menu de navigation Bootstrap 5
 * Association Sportive - Projet ASCG
 */

// Inclusion de la configuration (si pas déjà fait)
if (!isset($pdo)) {
    require_once 'config.php';
}

// Récupération du nom de la page courante pour le menu actif
$pageCourante = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titrePage) ? echapper($titrePage) . ' - ' : ''; ?>Association Sportive</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Styles personnalisés -->
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .table-actions {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <!-- Barre de navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-trophy"></i> Association Sportive
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="menuPrincipal">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <!-- Accueil -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $pageCourante === 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="bi bi-house"></i> Accueil
                        </a>
                    </li>
                    
                    <!-- Adhérents -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array($pageCourante, ['adherents.php', 'adherent_formulaire.php']) ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-people"></i> Adhérents
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="adherents.php">
                                    <i class="bi bi-list"></i> Liste des adhérents
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="adherent_formulaire.php">
                                    <i class="bi bi-person-plus"></i> Ajouter un adhérent
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Inscriptions -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array($pageCourante, ['inscriptions.php', 'gestion_adhesion.php']) ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-card-checklist"></i> Inscriptions
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="inscriptions.php">
                                    <i class="bi bi-list"></i> Liste des inscriptions
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="gestion_adhesion.php">
                                    <i class="bi bi-plus-circle"></i> Nouvelle inscription
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Activités -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array($pageCourante, ['activites.php', 'activite_formulaire.php']) ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-calendar-event"></i> Activités
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="activites.php">
                                    <i class="bi bi-list"></i> Liste des activités
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="activite_formulaire.php">
                                    <i class="bi bi-plus-circle"></i> Ajouter une activité
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Sections -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array($pageCourante, ['sections.php', 'section_formulaire.php']) ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-diagram-3"></i> Sections
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="sections.php">
                                    <i class="bi bi-list"></i> Liste des sections
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="section_formulaire.php">
                                    <i class="bi bi-plus-circle"></i> Ajouter une section
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
                
                <!-- Recherche (optionnel) -->
                <form class="d-flex" role="search" action="adherents.php" method="get">
                    <input class="form-control me-2" type="search" name="recherche" placeholder="Rechercher..." aria-label="Rechercher">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </nav>
    
    <!-- Contenu principal -->
    <main class="container mb-4">
