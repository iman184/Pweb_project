<?php
// ============================================================
//  index.php — Page d'accueil
// ============================================================
<?php
include 'config.php';
?>
require_once('includes/auth.php');
// Si déjà connecté → rediriger vers la page de rôle appropriée
if (is_logged_in()) {
    header('Location: ' . get_dashboard_url());
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ── Navbar ──────────────────────────────────────────────── -->
<nav class="navbar">
    <div class="nav-logo">
        <div class="nav-logo-box"><span>FI</span></div>
        <div>
            <div class="nav-brand"><?= APP_NAME ?></div>
            <div class="nav-brand-sub"><?= APP_SUB ?></div>
        </div>
    </div>
    <div class="nav-links">
        <a href="index.php"   class="nav-link active">Accueil</a>
        <a href="login.php"   class="nav-link">Connexion</a>
        <a href="register.php" class="nav-link">Inscription</a>
    </div>
    <a href="login.php"><button class="btn-nav">Se connecter</button></a>
</nav>

<!-- ── Hero ────────────────────────────────────────────────── -->
<section class="hero">
    <div class="hero-tag">Année universitaire <?= APP_YEAR ?></div>
    <div class="hero-title">
        Gestion de la scolarité<br>
        Faculté d'Informatique – USTHB
    </div>
    <div class="hero-sub">
        Plateforme centralisée pour la gestion des étudiants, des notes, des modules et des relevés académiques.
    </div>
    <div class="hero-btns">
        <a href="login.php">   <button class="btn-primary">Se connecter</button></a>
        <a href="register.php"><button class="btn-outline">Créer un compte</button></a>
    </div>
</section>

<!-- ── Features ────────────────────────────────────────────── -->
<section class="features">
    <div class="feat-card">
        <div class="feat-icon blue">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="5" r="3" fill="#185FA5"/>
                <path d="M2 13c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="#185FA5" stroke-width="1.5" fill="none"/>
            </svg>
        </div>
        <div class="feat-title">Gestion des étudiants</div>
        <div class="feat-desc">Ajout, modification, suppression et consultation des dossiers étudiants. Suivi complet du parcours académique.</div>
    </div>
    <div class="feat-card">
        <div class="feat-icon green">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none">
                <path d="M3 3h10v10H3z" fill="none" stroke="#3B6D11" stroke-width="1.2"/>
                <path d="M6 7l2 2 4-4" stroke="#3B6D11" stroke-width="1.5" fill="none"/>
            </svg>
        </div>
        <div class="feat-title">Notes et moyennes</div>
        <div class="feat-desc">Saisie des notes par les enseignants, calcul automatique des moyennes pondérées et génération des relevés.</div>
    </div>
    <div class="feat-card">
        <div class="feat-icon amber">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none">
                <rect x="2" y="2" width="12" height="2.5" rx="1" fill="#854F0B"/>
                <rect x="2" y="6.5" width="12" height="2.5" rx="1" fill="#854F0B" opacity=".6"/>
                <rect x="2" y="11" width="8" height="2.5" rx="1" fill="#854F0B" opacity=".3"/>
            </svg>
        </div>
        <div class="feat-title">Statistiques et rapports</div>
        <div class="feat-desc">Tableaux de bord avec statistiques par niveau, module et promotion. Relevés de notes téléchargeables.</div>
    </div>
</section>

<!-- ── Rôles ────────────────────────────────────────────────── -->
<section class="roles-section">
    <div class="section-label">Espaces disponibles</div>
    <div class="roles-grid">
        <a href="login.php?role=etudiant" class="role-card">
            <div class="role-dot blue"></div>
            <div class="role-name">Étudiant</div>
            <div class="role-desc">Consulter vos notes, relevés et résultats académiques.</div>
        </a>
        <a href="login.php?role=enseignant" class="role-card">
            <div class="role-dot green"></div>
            <div class="role-name">Enseignant</div>
            <div class="role-desc">Saisir les notes, gérer vos modules et suivre les résultats.</div>
        </a>
        <a href="login.php?role=admin" class="role-card">
            <div class="role-dot amber"></div>
            <div class="role-name">Administrateur</div>
            <div class="role-desc">Gestion complète : étudiants, enseignants, modules et inscriptions.</div>
        </a>
    </div>
</section>

<!-- ── À propos ──────────────────────────────────────────────── -->
<section class="about-section">
    <div class="about-title">À propos de la plateforme</div>
    <div class="about-text">
        Cette plateforme est développée pour la Faculté d'Informatique de l'USTHB (Université des Sciences et de la Technologie Houari Boumediene).
        Elle permet une gestion complète et centralisée de la scolarité : suivi des étudiants, saisie des notes, gestion des modules et génération des relevés académiques.
    </div>
    <div class="stats-bar">
        <div>
            <div class="stat-n">+1200</div>
            <div class="stat-l">Étudiants inscrits</div>
        </div>
        <div>
            <div class="stat-n">80+</div>
            <div class="stat-l">Enseignants</div>
        </div>
        <div>
            <div class="stat-n">30+</div>
            <div class="stat-l">Modules actifs</div>
        </div>
        <div>
            <div class="stat-n">4</div>
            <div class="stat-l">Niveaux</div>
        </div>
    </div>
</section>

<footer class="footer">
    © <?= date('Y') ?> <?= APP_NAME ?> · <?= APP_SUB ?>
</footer>

</body>
</html>