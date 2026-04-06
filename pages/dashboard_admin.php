<?php
// ============================================================
//  pages/dashboard_admin.php — Espace Administrateur
// ============================================================
$user_id = $_SESSION['user_id'];
$pdo     = get_pdo();

// Infos admin
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$user_id]);
$admin = $stmt->fetch();

$notif = '';

// ── Actions POST ──────────────────────────────────────────

// Supprimer un étudiant
if (isset($_POST['delete_etud'])) {
    $pdo->prepare("DELETE FROM etudiants WHERE id = ?")->execute([(int)$_POST['del_id']]);
    $notif = '<div class="notif notif-ok">Étudiant supprimé.</div>';
}

// Supprimer un module
if (isset($_POST['delete_mod'])) {
    $pdo->prepare("DELETE FROM modules WHERE id = ?")->execute([(int)$_POST['del_id']]);
    $notif = '<div class="notif notif-ok">Module supprimé.</div>';
}

// Ajouter un étudiant
if (isset($_POST['add_etud'])) {
    $hash = password_hash($_POST['ae_pw'], PASSWORD_BCRYPT);
    try {
        $stmt = $pdo->prepare("INSERT INTO etudiants (nom, prenom, email, matricule, niveau, mot_de_passe) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$_POST['ae_nom'], $_POST['ae_prenom'], $_POST['ae_email'], $_POST['ae_matricule'], $_POST['ae_niveau'], $hash]);
        $notif = '<div class="notif notif-ok">Étudiant ajouté avec succès.</div>';
    } catch (\PDOException $e) {
        $notif = '<div class="notif notif-err">Erreur : email ou matricule déjà utilisé.</div>';
    }
}

// Ajouter un module
if (isset($_POST['add_mod'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO modules (code, intitule, coefficient, niveau, enseignant_id, annee_univ) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$_POST['mod_code'], $_POST['mod_intitule'], (int)$_POST['mod_coeff'], $_POST['mod_niveau'], $_POST['mod_ens'] ?: null, '2025/2026']);
        $notif = '<div class="notif notif-ok">Module créé avec succès.</div>';
    } catch (\PDOException $e) {
        $notif = '<div class="notif notif-err">Erreur : ce code de module existe déjà.</div>';
    }
}

// Inscrire un étudiant
if (isset($_POST['do_inscr'])) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO inscriptions (etudiant_id, module_id, annee_univ) VALUES (?,?,?)");
        $stmt->execute([(int)$_POST['inscr_etud'], (int)$_POST['inscr_mod'], '2025/2026']);
        $notif = '<div class="notif notif-ok">Inscription enregistrée.</div>';
    } catch (\PDOException $e) {
        $notif = '<div class="notif notif-err">Erreur lors de l\'inscription.</div>';
    }
}

// ── Données ───────────────────────────────────────────────
$etudiants  = $pdo->query("SELECT * FROM etudiants  ORDER BY nom, prenom")->fetchAll();
$enseignants= $pdo->query("SELECT * FROM enseignants ORDER BY nom, prenom")->fetchAll();
$modules    = $pdo->query("SELECT m.*, CONCAT(e.grade,' ',e.nom) as ens_nom FROM modules m LEFT JOIN enseignants e ON e.id = m.enseignant_id ORDER BY m.code")->fetchAll();

// Stats globales
$nb_etudiants   = count($etudiants);
$nb_enseignants = count($enseignants);
$nb_modules     = count($modules);
$nb_notes       = $pdo->query("SELECT COUNT(*) FROM notes WHERE annee_univ='2025/2026'")->fetchColumn();

$panel = $_GET['panel'] ?? 'apercu';
$initials = strtoupper(substr($admin['prenom'], 0, 1) . substr($admin['nom'], 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Administrateur – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-inline { display:none; margin-top:14px; padding:14px; background:var(--color-bg-secondary); border-radius:var(--radius-md); }
        .form-inline.show { display:block; }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-logo">
        <div class="logo-box amber"><span>FI</span></div>
        <div>
            <div class="brand"><?= APP_NAME ?></div>
            <div class="brand-sub">Espace administrateur</div>
        </div>
    </div>
    <div class="topbar-right">
        <div class="avatar av-amber"><?= h($initials) ?></div>
        <div>
            <div class="uname"><?= h($admin['prenom'] . ' ' . $admin['nom']) ?></div>
            <div class="urole">Admin · <?= h($admin['service'] ?? 'Scolarité') ?></div>
        </div>
        <a href="logout.php"><button class="btn-sm">Déconnexion</button></a>
    </div>
</div>

<div class="dash-layout">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="nav-section">Administration</div>
        <a href="?panel=apercu"    class="nav-item <?= $panel === 'apercu'    ? 'active-amber' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><rect x="2" y="2" width="5" height="5" rx="1" fill="none" stroke="currentColor" stroke-width="1.3"/><rect x="9" y="2" width="5" height="5" rx="1" fill="none" stroke="currentColor" stroke-width="1.3"/><rect x="2" y="9" width="5" height="5" rx="1" fill="none" stroke="currentColor" stroke-width="1.3"/><rect x="9" y="9" width="5" height="5" rx="1" fill="none" stroke="currentColor" stroke-width="1.3"/></svg>
            Aperçu général
        </a>
        <a href="?panel=etudiants" class="nav-item <?= $panel === 'etudiants' ? 'active-amber' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="5" r="3" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M2 14c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="currentColor" stroke-width="1.4" fill="none"/></svg>
            Étudiants
        </a>
        <a href="?panel=enseignants" class="nav-item <?= $panel === 'enseignants' ? 'active-amber' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><circle cx="6" cy="5" r="2.5" fill="none" stroke="currentColor" stroke-width="1.3"/><path d="M1 13c0-2.8 2.2-5 5-5" stroke="currentColor" stroke-width="1.3" fill="none"/><circle cx="12" cy="5" r="2" fill="none" stroke="currentColor" stroke-width="1.3"/><path d="M10 13h4" stroke="currentColor" stroke-width="1.3"/><path d="M12 11v4" stroke="currentColor" stroke-width="1.3"/></svg>
            Enseignants
        </a>
        <a href="?panel=modules"   class="nav-item <?= $panel === 'modules'   ? 'active-amber' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><rect x="2" y="2" width="12" height="2.5" rx="1" fill="none" stroke="currentColor" stroke-width="1.2"/><rect x="2" y="6.5" width="12" height="2.5" rx="1" fill="none" stroke="currentColor" stroke-width="1.2"/><rect x="2" y="11" width="8" height="2.5" rx="1" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
            Modules
        </a>
        <a href="?panel=inscriptions" class="nav-item <?= $panel === 'inscriptions' ? 'active-amber' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M3 3h10v10H3z" fill="none" stroke="currentColor" stroke-width="1.3"/><path d="M6 7l2 2 4-4" stroke="currentColor" stroke-width="1.4" fill="none"/></svg>
            Inscriptions
        </a>
        <a href="?panel=stats"     class="nav-item <?= $panel === 'stats'     ? 'active-amber' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><rect x="2" y="9" width="3" height="5" fill="none" stroke="currentColor" stroke-width="1.3"/><rect x="6.5" y="6" width="3" height="8" fill="none" stroke="currentColor" stroke-width="1.3"/><rect x="11" y="3" width="3" height="11" fill="none" stroke="currentColor" stroke-width="1.3"/></svg>
            Statistiques
        </a>
    </div>

    <div class="main-content">
        <?= $notif ?>

        <?php if ($panel === 'apercu'): ?>
        <!-- ── Aperçu ── -->
        <div class="page-head">
            <div class="page-title">Aperçu général</div>
            <div class="page-sub">Tableau de bord de la scolarité – <?= APP_YEAR ?></div>
        </div>
        <div class="stat-grid sg4">
            <div class="stat-card"><div class="stat-card-label">Étudiants</div><div class="stat-card-val val-amber"><?= $nb_etudiants ?></div></div>
            <div class="stat-card"><div class="stat-card-label">Enseignants</div><div class="stat-card-val val-amber"><?= $nb_enseignants ?></div></div>
            <div class="stat-card"><div class="stat-card-label">Modules actifs</div><div class="stat-card-val val-amber"><?= $nb_modules ?></div></div>
            <div class="stat-card"><div class="stat-card-label">Notes saisies</div><div class="stat-card-val val-amber"><?= $nb_notes ?></div></div>
        </div>

        <!-- Accès rapides -->
        <div class="stat-grid sg3">
            <a href="?panel=etudiants" style="text-decoration:none;">
                <div class="card" style="cursor:pointer;text-align:center;padding:24px;">
                    <div style="font-size:24px;margin-bottom:8px;">👤</div>
                    <div class="card-title">Gérer les étudiants</div>
                    <div style="font-size:11px;color:var(--color-text-secondary);margin-top:4px;"><?= $nb_etudiants ?> inscrits</div>
                </div>
            </a>
            <a href="?panel=modules" style="text-decoration:none;">
                <div class="card" style="cursor:pointer;text-align:center;padding:24px;">
                    <div style="font-size:24px;margin-bottom:8px;">📚</div>
                    <div class="card-title">Gérer les modules</div>
                    <div style="font-size:11px;color:var(--color-text-secondary);margin-top:4px;"><?= $nb_modules ?> modules</div>
                </div>
            </a>
            <a href="?panel=inscriptions" style="text-decoration:none;">
                <div class="card" style="cursor:pointer;text-align:center;padding:24px;">
                    <div style="font-size:24px;margin-bottom:8px;">✅</div>
                    <div class="card-title">Inscriptions</div>
                    <div style="font-size:11px;color:var(--color-text-secondary);margin-top:4px;">Inscrire un étudiant</div>
                </div>
            </a>
        </div>

        <?php elseif ($panel === 'etudiants'): ?>
        <!-- ── Étudiants ── -->
        <div class="page-head">
            <div class="page-title">Gestion des étudiants</div>
            <div class="page-sub">Ajouter, consulter et supprimer des étudiants</div>
        </div>

        <!-- Bouton Ajouter -->
        <div style="margin-bottom:14px;">
            <button class="btn-blue" onclick="toggleForm('add-etud-form')">+ Ajouter un étudiant</button>
        </div>
        <div id="add-etud-form" class="form-inline">
            <form method="POST">
                <div class="stat-grid sg3">
                    <div class="fg"><label>Nom</label><input type="text" name="ae_nom" required placeholder="Nom"></div>
                    <div class="fg"><label>Prénom</label><input type="text" name="ae_prenom" required placeholder="Prénom"></div>
                    <div class="fg"><label>Email</label><input type="email" name="ae_email" required placeholder="email@usthb.dz"></div>
                    <div class="fg"><label>Matricule</label><input type="text" name="ae_matricule" required placeholder="12345"></div>
                    <div class="fg">
                        <label>Niveau</label>
                        <select name="ae_niveau">
                            <?php foreach (['L1 Info','L2 ISIL','L3 Info','M1 ISIL','M2 ISIL'] as $niv): ?>
                            <option><?= $niv ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg"><label>Mot de passe</label><input type="password" name="ae_pw" required placeholder="••••••••"></div>
                </div>
                <button type="submit" name="add_etud" class="btn-blue">Enregistrer</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Liste des étudiants</div><span class="badge b-mid"><?= $nb_etudiants ?> étudiants</span></div>
            <table>
                <thead><tr><th>Matricule</th><th>Nom & Prénom</th><th>Email</th><th>Niveau</th><th>Inscrit le</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($etudiants as $e): ?>
                <tr>
                    <td><?= h($e['matricule']) ?></td>
                    <td><?= h($e['nom'] . ' ' . $e['prenom']) ?></td>
                    <td><?= h($e['email']) ?></td>
                    <td><?= h($e['niveau']) ?></td>
                    <td><?= date('d/m/Y', strtotime($e['created_at'])) ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cet étudiant ?')">
                            <input type="hidden" name="del_id" value="<?= $e['id'] ?>">
                            <button type="submit" name="delete_etud" class="btn-action btn-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($panel === 'enseignants'): ?>
        <!-- ── Enseignants ── -->
        <div class="page-head">
            <div class="page-title">Liste des enseignants</div>
            <div class="page-sub"><?= $nb_enseignants ?> enseignants enregistrés</div>
        </div>
        <div class="card">
            <table>
                <thead><tr><th>ID</th><th>Nom & Prénom</th><th>Grade</th><th>Email</th><th>Département</th><th>Spécialité</th></tr></thead>
                <tbody>
                <?php foreach ($enseignants as $e): ?>
                <tr>
                    <td>E<?= str_pad($e['id'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td><?= h($e['nom'] . ' ' . $e['prenom']) ?></td>
                    <td><?= h($e['grade']) ?></td>
                    <td><?= h($e['email']) ?></td>
                    <td><?= h($e['departement']) ?></td>
                    <td><?= h($e['specialite'] ?? '–') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($panel === 'modules'): ?>
        <!-- ── Modules ── -->
        <div class="page-head">
            <div class="page-title">Gestion des modules</div>
            <div class="page-sub">Créer et assigner des modules aux enseignants</div>
        </div>

        <div style="margin-bottom:14px;">
            <button class="btn-blue" onclick="toggleForm('add-mod-form')">+ Nouveau module</button>
        </div>
        <div id="add-mod-form" class="form-inline">
            <form method="POST">
                <div class="stat-grid sg3">
                    <div class="fg"><label>Code</label><input type="text" name="mod_code" required placeholder="ex : GL01"></div>
                    <div class="fg"><label>Intitulé</label><input type="text" name="mod_intitule" required placeholder="ex : Génie Logiciel"></div>
                    <div class="fg"><label>Coefficient</label><input type="number" name="mod_coeff" min="1" max="6" value="2" required></div>
                    <div class="fg">
                        <label>Niveau</label>
                        <select name="mod_niveau">
                            <?php foreach (['L1 Info','L2 ISIL','L3 Info','M1 ISIL','M2 ISIL'] as $niv): ?>
                            <option><?= $niv ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Enseignant</label>
                        <select name="mod_ens">
                            <option value="">– Non assigné –</option>
                            <?php foreach ($enseignants as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= h($e['grade'] . ' ' . $e['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_mod" class="btn-blue">Créer le module</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Liste des modules</div></div>
            <table>
                <thead><tr><th>Code</th><th>Intitulé</th><th>Coeff.</th><th>Niveau</th><th>Enseignant</th><th>Moy. classe</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($modules as $m):
                    $stmt = $pdo->prepare("SELECT AVG(note) FROM notes WHERE module_id = ? AND annee_univ='2025/2026'");
                    $stmt->execute([$m['id']]);
                    $avg = $stmt->fetchColumn();
                    $avg_str = $avg ? number_format($avg, 2) : '–';
                    $avg_color = $avg ? ($avg >= 10 ? 'var(--color-green)' : 'var(--color-red)') : 'var(--color-text-secondary)';
                ?>
                <tr>
                    <td><?= h($m['code']) ?></td>
                    <td><?= h($m['intitule']) ?></td>
                    <td><?= h($m['coefficient']) ?></td>
                    <td><?= h($m['niveau']) ?></td>
                    <td><?= h($m['ens_nom'] ?? '–') ?></td>
                    <td style="font-weight:600;color:<?= $avg_color ?>"><?= $avg_str ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce module ?')">
                            <input type="hidden" name="del_id" value="<?= $m['id'] ?>">
                            <button type="submit" name="delete_mod" class="btn-action btn-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($panel === 'inscriptions'): ?>
        <!-- ── Inscriptions ── -->
        <div class="page-head">
            <div class="page-title">Inscriptions</div>
            <div class="page-sub">Inscrire un étudiant à un module</div>
        </div>
        <div class="card" style="max-width:440px;">
            <form method="POST">
                <div class="fg">
                    <label>Étudiant</label>
                    <select name="inscr_etud" required>
                        <?php foreach ($etudiants as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= h($e['matricule'] . ' – ' . $e['nom'] . ' ' . $e['prenom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label>Module</label>
                    <select name="inscr_mod" required>
                        <?php foreach ($modules as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= h($m['code'] . ' – ' . $m['intitule']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="do_inscr" class="btn-blue" style="width:100%;padding:9px;margin-top:4px;">Inscrire</button>
            </form>
        </div>

        <!-- Liste des inscriptions existantes -->
        <div class="card">
            <div class="card-header"><div class="card-title">Inscriptions actives – <?= APP_YEAR ?></div></div>
            <?php
            $inscriptions = $pdo->query("
                SELECT e.matricule, CONCAT(e.prenom,' ',e.nom) as etud_nom, m.code, m.intitule
                FROM inscriptions i
                JOIN etudiants e ON e.id = i.etudiant_id
                JOIN modules m ON m.id = i.module_id
                WHERE i.annee_univ = '2025/2026'
                ORDER BY e.nom, m.code
            ")->fetchAll();
            ?>
            <table>
                <thead><tr><th>Matricule</th><th>Étudiant</th><th>Code</th><th>Module</th></tr></thead>
                <tbody>
                <?php foreach ($inscriptions as $i): ?>
                <tr>
                    <td><?= h($i['matricule']) ?></td>
                    <td><?= h($i['etud_nom']) ?></td>
                    <td><?= h($i['code']) ?></td>
                    <td><?= h($i['intitule']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php elseif ($panel === 'stats'): ?>
        <!-- ── Statistiques ── -->
        <div class="page-head">
            <div class="page-title">Statistiques académiques</div>
            <div class="page-sub">Résultats globaux – <?= APP_YEAR ?></div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Résultats par module</div></div>
            <table>
                <thead><tr><th>Module</th><th>Inscrits</th><th>Notes saisies</th><th>Admis (≥10)</th><th>Ajournés</th><th>Taux réussite</th><th>Moyenne</th></tr></thead>
                <tbody>
                <?php foreach ($modules as $m):
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inscriptions WHERE module_id = ? AND annee_univ='2025/2026'");
                    $stmt->execute([$m['id']]); $nb_inscr = $stmt->fetchColumn();

                    $stmt = $pdo->prepare("SELECT note FROM notes WHERE module_id = ? AND annee_univ='2025/2026'");
                    $stmt->execute([$m['id']]); $all_notes = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    $nb_notes_mod = count($all_notes);
                    $admis_mod = count(array_filter($all_notes, fn($n) => $n >= 10));
                    $ajourn_mod = $nb_notes_mod - $admis_mod;
                    $taux = $nb_notes_mod > 0 ? round($admis_mod / $nb_notes_mod * 100) : 0;
                    $avg_mod = $nb_notes_mod > 0 ? array_sum($all_notes) / $nb_notes_mod : null;
                    $avg_color = $avg_mod ? ($avg_mod >= 10 ? 'var(--color-green)' : 'var(--color-red)') : 'var(--color-text-secondary)';

                    $taux_badge = $taux >= 70 ? 'b-ok' : ($taux >= 50 ? 'b-mid' : 'b-no');
                ?>
                <tr>
                    <td><?= h($m['code']) ?> – <?= h($m['intitule']) ?></td>
                    <td><?= $nb_inscr ?></td>
                    <td><?= $nb_notes_mod ?></td>
                    <td><span class="badge b-ok"><?= $admis_mod ?></span></td>
                    <td><span class="badge b-no"><?= $ajourn_mod ?></span></td>
                    <td><span class="badge <?= $taux_badge ?>"><?= $taux ?>%</span></td>
                    <td style="font-weight:600;color:<?= $avg_color ?>"><?= $avg_mod ? number_format($avg_mod, 2) : '–' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div><!-- /main-content -->
</div><!-- /dash-layout -->

<script>
function toggleForm(id) {
    var el = document.getElementById(id);
    el.classList.toggle('show');
}
</script>
</body>
</html>
