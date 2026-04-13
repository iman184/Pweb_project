-- ============================================================
--  schema.sql — Base de données USTHB Scolarité
-- ============================================================

CREATE DATABASE IF NOT EXISTS usthb_scolarite
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE usthb_scolarite;

-- ── Étudiants ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS etudiants (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    nom                  VARCHAR(80)  NOT NULL,
    prenom               VARCHAR(80)  NOT NULL,
    email                VARCHAR(120) NOT NULL UNIQUE,
    matricule            VARCHAR(20)  NOT NULL UNIQUE,
    niveau               VARCHAR(30)  NOT NULL DEFAULT 'L1 Info',
    date_naissance       DATE,
    mot_de_passe         VARCHAR(255) NOT NULL,
    must_change_password TINYINT(1)   NOT NULL DEFAULT 1,
    actif                TINYINT(1)   NOT NULL DEFAULT 1,
    created_at           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── Enseignants ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS enseignants (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    nom                  VARCHAR(80)  NOT NULL,
    prenom               VARCHAR(80)  NOT NULL,
    email                VARCHAR(120) NOT NULL UNIQUE,
    grade                VARCHAR(30)  NOT NULL DEFAULT 'Dr.',
    departement          VARCHAR(80)  NOT NULL DEFAULT 'Informatique',
    specialite           VARCHAR(120),
    mot_de_passe         VARCHAR(255) NOT NULL,
    must_change_password TINYINT(1)   NOT NULL DEFAULT 1,
    actif                TINYINT(1)   NOT NULL DEFAULT 1,
    created_at           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── Administrateurs ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(80)  NOT NULL,
    prenom          VARCHAR(80)  NOT NULL,
    email           VARCHAR(120) NOT NULL UNIQUE,
    service         VARCHAR(120),
    mot_de_passe    VARCHAR(255) NOT NULL,
    actif           TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── Modules ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS modules (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(20)  NOT NULL UNIQUE,
    intitule        VARCHAR(120) NOT NULL,
    coefficient     INT          NOT NULL DEFAULT 1,
    niveau          VARCHAR(30)  NOT NULL DEFAULT 'L2 ISIL',
    enseignant_id   INT,
    annee_univ      VARCHAR(10)  NOT NULL DEFAULT '2025/2026',
    FOREIGN KEY (enseignant_id) REFERENCES enseignants(id) ON DELETE SET NULL
);

-- ── Inscriptions (étudiant ↔ module) ─────────────────────
CREATE TABLE IF NOT EXISTS inscriptions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id     INT NOT NULL,
    module_id       INT NOT NULL,
    annee_univ      VARCHAR(10) NOT NULL DEFAULT '2025/2026',
    UNIQUE KEY uq_inscr (etudiant_id, module_id, annee_univ),
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id)  ON DELETE CASCADE,
    FOREIGN KEY (module_id)   REFERENCES modules(id)    ON DELETE CASCADE
);

-- ── Notes ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id     INT NOT NULL,
    module_id       INT NOT NULL,
    note            DECIMAL(4,2) NOT NULL CHECK (note >= 0 AND note <= 20),
    annee_univ      VARCHAR(10) NOT NULL DEFAULT '2025/2026',
    UNIQUE KEY uq_note (etudiant_id, module_id, annee_univ),
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id)   REFERENCES modules(id)   ON DELETE CASCADE
);

-- ============================================================
--  Données de démonstration
-- ============================================================

-- Mot de passe commun : "password123"  (bcrypt hash)
SET @hash = '$2y$10$QOWcGH7HuNxsIFwWBt7qpufAPxd2.KRoAvb3uV1VAPVbShIYXXCRi';

INSERT IGNORE INTO etudiants (nom, prenom, email, matricule, niveau, date_naissance, mot_de_passe, must_change_password)
VALUES
    ('Karim',  'Ali',   'ali.karim@usthb.dz',   '12345', 'L2 ISIL',  '2002-03-14', @hash, 0),
    ('Houda',  'Leila', 'leila.houda@usthb.dz',  '67899', 'L2 ISIL',  '2002-07-22', @hash, 0),
    ('Bouzid', 'Omar',  'omar.bouzid@usthb.dz',  '34521', 'L3 Info',  '2001-11-05', @hash, 0);

INSERT IGNORE INTO enseignants (nom, prenom, email, grade, departement, specialite, mot_de_passe, must_change_password)
VALUES
    ('Laachemi',  'Salim',  'laachemi@usthb.dz',  'Dr.', 'Informatique',  'Bases de données, Web', @hash, 0),
    ('Hamidi',    'Nadia',  'hamidi@usthb.dz',    'Pr.', 'Math & Info',   'Algorithmique, Maths',  @hash, 0),
    ('Benali',    'Youssef','benali@usthb.dz',     'Dr.', 'Informatique',  'Réseaux',               @hash, 0);

INSERT IGNORE INTO admins (nom, prenom, email, service, mot_de_passe)
VALUES ('Admin', 'Scolarité', 'admin@usthb.dz', 'Scolarité centrale', @hash);

INSERT IGNORE INTO modules (code, intitule, coefficient, niveau, enseignant_id, annee_univ)
VALUES
    ('PWEB', 'Programmation Web',      3, 'L2 ISIL', 1, '2025/2026'),
    ('BD01', 'Bases de données',        3, 'L2 ISIL', 1, '2025/2026'),
    ('ALGO', 'Algorithmique avancée',   4, 'L2 ISIL', 2, '2025/2026'),
    ('RES1', 'Réseaux informatiques',   3, 'L2 ISIL', 3, '2025/2026');

-- Inscriptions
INSERT IGNORE INTO inscriptions (etudiant_id, module_id, annee_univ)
VALUES (1,1,'2025/2026'),(1,2,'2025/2026'),(1,3,'2025/2026'),(1,4,'2025/2026'),
       (2,1,'2025/2026'),(2,2,'2025/2026'),(2,3,'2025/2026'),
       (3,1,'2025/2026'),(3,3,'2025/2026'),(3,4,'2025/2026');

-- Notes
INSERT IGNORE INTO notes (etudiant_id, module_id, note, annee_univ)
VALUES
    (1,1,18.00,'2025/2026'),(1,2,12.50,'2025/2026'),(1,3,15.00,'2025/2026'),(1,4,9.50,'2025/2026'),
    (2,1,14.00,'2025/2026'),(2,2, 8.00,'2025/2026'),(2,3,11.00,'2025/2026'),
    (3,1,16.00,'2025/2026'),(3,3,13.00,'2025/2026'),(3,4,10.00,'2025/2026');
    -- ── Posts / Announcements ─────────────────────────────────
CREATE TABLE IF NOT EXISTS posts (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    enseignant_id INT NOT NULL,
    module_id     INT NOT NULL,
    contenu       TEXT NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enseignant_id) REFERENCES enseignants(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id)     REFERENCES modules(id)     ON DELETE CASCADE
);

-- ── Comments ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS commentaires (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    post_id      INT NOT NULL,
    etudiant_id  INT NOT NULL,
    contenu      TEXT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id)      REFERENCES posts(id)      ON DELETE CASCADE,
    FOREIGN KEY (etudiant_id)  REFERENCES etudiants(id)  ON DELETE CASCADE
);

-- ── Notifications ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id  INT NOT NULL,
    post_id      INT NOT NULL,
    lu           TINYINT(1) DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id)     REFERENCES posts(id)     ON DELETE CASCADE
);

-- ── Absences ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS absences (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id  INT NOT NULL,
    type         ENUM('td','tp') NOT NULL,
    session_num  TINYINT NOT NULL,
    module_id    INT NOT NULL,
    statut       ENUM('P','A') NOT NULL DEFAULT 'P',
    annee_univ   VARCHAR(10) NOT NULL DEFAULT '2025/2026',
    UNIQUE KEY uq_abs (etudiant_id, type, session_num, module_id, annee_univ),
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id)   REFERENCES modules(id)   ON DELETE CASCADE
);

-- ── Timetable ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS emploi_du_temps (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    enseignant_id   INT NOT NULL,
    jour            VARCHAR(15) NOT NULL,
    heure_debut     VARCHAR(10) NOT NULL,
    heure_fin       VARCHAR(10) NOT NULL,
    type_seance     ENUM('Cours','TD','TP') NOT NULL,
    groupe          VARCHAR(10) NOT NULL DEFAULT 'All',
    salle           VARCHAR(50),
    annee_univ      VARCHAR(10) NOT NULL DEFAULT '2025/2026',
    FOREIGN KEY (enseignant_id) REFERENCES enseignants(id) ON DELETE CASCADE
);
