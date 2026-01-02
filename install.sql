-- ============================================
-- Script d'installation - Base de données Association Sportive
-- Date: 2026-01-02
-- ============================================

-- Suppression de la base si elle existe et création
DROP DATABASE IF EXISTS association_sportive;
CREATE DATABASE association_sportive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE association_sportive;

-- ============================================
-- CRÉATION DES TABLES
-- ============================================

-- Table LIEU (aucune dépendance)
CREATE TABLE LIEU (
    Id_Lieu INT AUTO_INCREMENT,
    Nom VARCHAR(100) NOT NULL,
    Adresse VARCHAR(255) NOT NULL,
    Capacite INT,
    PRIMARY KEY (Id_Lieu)
) ENGINE=InnoDB;

-- Table BENEVOLE (aucune dépendance)
CREATE TABLE BENEVOLE (
    Id_Benevole INT AUTO_INCREMENT,
    Nom VARCHAR(50) NOT NULL,
    Prenom VARCHAR(50) NOT NULL,
    Telephone VARCHAR(20),
    Courriel VARCHAR(100),
    PRIMARY KEY (Id_Benevole)
) ENGINE=InnoDB;

-- Table ADHERENT (aucune dépendance)
CREATE TABLE ADHERENT (
    Num_Adherent INT AUTO_INCREMENT,
    Nom VARCHAR(50) NOT NULL,
    Prenom VARCHAR(50) NOT NULL,
    Adresse VARCHAR(255),
    Code_Postal VARCHAR(10),
    Ville VARCHAR(100),
    Telephone VARCHAR(20),
    Courriel VARCHAR(100),
    Date_Naissance DATE,
    PRIMARY KEY (Num_Adherent)
) ENGINE=InnoDB;

-- Table SECTION (dépend de BENEVOLE)
CREATE TABLE SECTION (
    Code_Section VARCHAR(10),
    Libelle VARCHAR(100) NOT NULL,
    Debut_Saison DATE,
    Id_Benevole INT,
    PRIMARY KEY (Code_Section),
    FOREIGN KEY (Id_Benevole) REFERENCES BENEVOLE(Id_Benevole)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table ACTIVITE (dépend de SECTION et LIEU)
CREATE TABLE ACTIVITE (
    Num_Activite INT AUTO_INCREMENT,
    Libelle VARCHAR(100) NOT NULL,
    Description TEXT,
    Jour_Semaine VARCHAR(20),
    Horaire TIME,
    Duree_Seance INT COMMENT 'Durée en minutes',
    Tarif_Annuel DECIMAL(10, 2),
    Code_Section VARCHAR(10),
    Id_Lieu INT,
    PRIMARY KEY (Num_Activite),
    FOREIGN KEY (Code_Section) REFERENCES SECTION(Code_Section)
        ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (Id_Lieu) REFERENCES LIEU(Id_Lieu)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table ADHESION (dépend de ADHERENT et ACTIVITE) - Clé primaire composite
CREATE TABLE ADHESION (
    Num_Adherent INT,
    Num_Activite INT,
    Date_Adhesion DATE NOT NULL,
    Nom_Banque VARCHAR(100),
    NumCheque VARCHAR(50),
    PRIMARY KEY (Num_Adherent, Num_Activite),
    FOREIGN KEY (Num_Adherent) REFERENCES ADHERENT(Num_Adherent)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Num_Activite) REFERENCES ACTIVITE(Num_Activite)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table BUREAU (dépend de BENEVOLE et SECTION) - Clé primaire composite
CREATE TABLE BUREAU (
    Id_Benevole INT,
    Code_Section VARCHAR(10),
    Fonction VARCHAR(100) NOT NULL,
    PRIMARY KEY (Id_Benevole, Code_Section),
    FOREIGN KEY (Id_Benevole) REFERENCES BENEVOLE(Id_Benevole)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Code_Section) REFERENCES SECTION(Code_Section)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table PARENTE (dépend de ADHERENT) - Clé primaire composite
CREATE TABLE PARENTE (
    Num_Adherent_1 INT,
    Num_Adherent_2 INT,
    Nature VARCHAR(50) NOT NULL COMMENT 'Ex: Parent, Enfant, Conjoint, Frère/Soeur',
    PRIMARY KEY (Num_Adherent_1, Num_Adherent_2),
    FOREIGN KEY (Num_Adherent_1) REFERENCES ADHERENT(Num_Adherent)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Num_Adherent_2) REFERENCES ADHERENT(Num_Adherent)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- INSERTION DES DONNÉES DE TEST
-- ============================================

-- Données LIEU
INSERT INTO LIEU (Nom, Adresse, Capacite) VALUES
('Gymnase Municipal', '12 Rue des Sports, 75001 Paris', 200),
('Stade Jean Bouin', '45 Avenue du Stade, 75001 Paris', 500),
('Piscine Olympique', '8 Boulevard Aquatique, 75002 Paris', 150),
('Salle Polyvalente', '23 Rue de la Mairie, 75001 Paris', 100);

-- Données BENEVOLE
INSERT INTO BENEVOLE (Nom, Prenom, Telephone, Courriel) VALUES
('Dupont', 'Marie', '0612345678', 'marie.dupont@email.fr'),
('Martin', 'Pierre', '0623456789', 'pierre.martin@email.fr'),
('Bernard', 'Sophie', '0634567890', 'sophie.bernard@email.fr'),
('Leroy', 'Jean', '0645678901', 'jean.leroy@email.fr');

-- Données ADHERENT
INSERT INTO ADHERENT (Nom, Prenom, Adresse, Code_Postal, Ville, Telephone, Courriel, Date_Naissance) VALUES
('Moreau', 'Lucas', '5 Rue des Lilas', '75003', 'Paris', '0656789012', 'lucas.moreau@email.fr', '1990-05-15'),
('Petit', 'Emma', '18 Avenue Victor Hugo', '75004', 'Paris', '0667890123', 'emma.petit@email.fr', '1985-08-22'),
('Roux', 'Thomas', '32 Boulevard Haussmann', '75005', 'Paris', '0678901234', 'thomas.roux@email.fr', '2010-03-10'),
('Moreau', 'Julie', '5 Rue des Lilas', '75003', 'Paris', '0656789013', 'julie.moreau@email.fr', '1988-11-28');

-- Données SECTION
INSERT INTO SECTION (Code_Section, Libelle, Debut_Saison, Id_Benevole) VALUES
('FOOT', 'Football', '2025-09-01', 1),
('BASKET', 'Basketball', '2025-09-01', 2),
('NATATION', 'Natation', '2025-09-15', 3),
('GYM', 'Gymnastique', '2025-09-01', 4);

-- Données ACTIVITE
INSERT INTO ACTIVITE (Libelle, Description, Jour_Semaine, Horaire, Duree_Seance, Tarif_Annuel, Code_Section, Id_Lieu) VALUES
('Football Adultes', 'Entraînement football pour adultes (18+)', 'Mercredi', '19:00:00', 90, 250.00, 'FOOT', 2),
('Football Jeunes', 'École de football pour les 8-14 ans', 'Samedi', '10:00:00', 60, 180.00, 'FOOT', 2),
('Basket Loisir', 'Basketball loisir tous niveaux', 'Mardi', '20:00:00', 90, 200.00, 'BASKET', 1),
('Natation Débutant', 'Cours de natation pour débutants', 'Lundi', '18:00:00', 45, 300.00, 'NATATION', 3);

-- Données ADHESION
INSERT INTO ADHESION (Num_Adherent, Num_Activite, Date_Adhesion, Nom_Banque, NumCheque) VALUES
(1, 1, '2025-09-05', 'Crédit Agricole', 'CHQ001234'),
(2, 3, '2025-09-10', 'BNP Paribas', 'CHQ005678'),
(3, 2, '2025-09-12', 'Société Générale', 'CHQ009012'),
(4, 4, '2025-09-20', 'Caisse d''Épargne', 'CHQ003456');

-- Données BUREAU
INSERT INTO BUREAU (Id_Benevole, Code_Section, Fonction) VALUES
(1, 'FOOT', 'Président'),
(2, 'BASKET', 'Secrétaire'),
(3, 'NATATION', 'Trésorier'),
(1, 'GYM', 'Vice-Président');

-- Données PARENTE
INSERT INTO PARENTE (Num_Adherent_1, Num_Adherent_2, Nature) VALUES
(1, 3, 'Parent'),
(3, 1, 'Enfant'),
(1, 4, 'Conjoint'),
(4, 1, 'Conjoint');

-- ============================================
-- VÉRIFICATION DES DONNÉES
-- ============================================

SELECT 'Installation terminée avec succès!' AS Message;
SELECT TABLE_NAME, TABLE_ROWS 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'association_sportive';
