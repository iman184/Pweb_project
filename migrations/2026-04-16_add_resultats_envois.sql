-- Store grade submissions sent by teachers to administration
CREATE TABLE IF NOT EXISTS resultats_envois (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    enseignant_id   INT NOT NULL,
    module_id       INT NOT NULL,
    annee_univ      VARCHAR(10) NOT NULL DEFAULT '2025/2026',
    nb_etudiants    INT NOT NULL DEFAULT 0,
    nb_notes        INT NOT NULL DEFAULT 0,
    moyenne_classe   DECIMAL(4,2) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enseignant_id) REFERENCES enseignants(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id)     REFERENCES modules(id)     ON DELETE CASCADE
);
