<?php
// ============================================================
//  pages/dashboard_enseignant.php — Espace Enseignant
// ============================================================
$user_id = $_SESSION['user_id'];
$pdo     = get_pdo();

// Infos enseignant
$stmt = $pdo->prepare("SELECT * FROM enseignants WHERE id = ?");
$stmt->execute([$user_id]);
$ens = $stmt->fetch();

// Modules de cet enseignant
$stmt = $pdo->prepare("SELECT * FROM modules WHERE enseignant_id = ? AND annee_univ = '2025/2026'");
$stmt->execute([$user_id]);
$modules = $stmt->fetchAll();

// Traitement : Sauvegarde de note
$notif = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_note'])) {
    $etudiant_id = (int)$_POST['etudiant_id'];
    $module_id   = (int)$_POST['module_id'];
    $note_val    = (float)str_replace(',', '.', $_POST['note_val']);

    if ($note_val < 0 || $note_val > 20) {
        $notif = '<div class="notif notif-err">Note invalide (0–20).</div>';
    } else {
        // Vérifier que le module appartient à cet enseignant
        $chk = $pdo->prepare("SELECT id FROM modules WHERE id = ? AND enseignant_id = ?");
        $chk->execute([$module_id, $user_id]);
        if ($chk->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO notes (etudiant_id, module_id, note, annee_univ)
                VALUES (?, ?, ?, '2025/2026')
                ON DUPLICATE KEY UPDATE note = VALUES(note)
            ");
            $stmt->execute([$etudiant_id, $module_id, $note_val]);
            $notif = '<div class="notif notif-ok">Note enregistrée avec succès.</div>';
        }
    }
}

// Panel actif
$panel = $_GET['panel'] ?? 'notes';
$initials = strtoupper(substr($ens['prenom'], 0, 1) . substr($ens['nom'], 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Enseignant – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-logo">
        <div class="logo-box green"><span>FI</span></div>
        <div>
            <div class="brand"><?= APP_NAME ?></div>
            <div class="brand-sub">Espace enseignant</div>
        </div>
    </div>
    <div class="topbar-right">
        <div class="avatar av-green"><?= h($initials) ?></div>
        <div>
            <div class="uname"><?= h($ens['grade'] . ' ' . $ens['nom']) ?></div>
            <div class="urole">Enseignant · <?= h($ens['departement']) ?></div>
        </div>
        <a href="logout.php"><button class="btn-sm">Déconnexion</button></a>
    </div>
</div>

<div class="dash-layout">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="nav-section">Mon espace</div>
        <a href="?panel=notes" class="nav-item <?= $panel === 'notes' ? 'active-green' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <path d="M3 3h10v10H3z" fill="none" stroke="currentColor" stroke-width="1.4"/>
                <path d="M6 7l2 2 4-4" stroke="currentColor" stroke-width="1.4" fill="none"/>
            </svg>
            Saisie des notes
        </a>
        <a href="?panel=modules" class="nav-item <?= $panel === 'modules' ? 'active-green' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <rect x="2" y="2" width="12" height="2.5" rx="1" fill="none" stroke="currentColor" stroke-width="1.2"/>
                <rect x="2" y="6.5" width="12" height="2.5" rx="1" fill="none" stroke="currentColor" stroke-width="1.2"/>
                <rect x="2" y="11" width="8" height="2.5" rx="1" fill="none" stroke="currentColor" stroke-width="1.2"/>
            </svg>
            Mes modules
        </a>
        <a href="?panel=profil" class="nav-item <?= $panel === 'profil' ? 'active-green' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="5" r="3" fill="none" stroke="currentColor" stroke-width="1.4"/>
                <path d="M2 14c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="currentColor" stroke-width="1.4" fill="none"/>
            </svg>
            Mon profil
        </a>
    </div>

    <div class="main-content">

    <?php if ($panel === 'notes'): ?>
        <!-- ── Panel : Saisie des notes ── -->
        <div class="page-head">
            <div class="page-title">Saisie des notes</div>
            <div class="page-sub">Enregistrer les notes par module et par étudiant</div>
        </div>

        <?= $notif ?>

        <?php foreach ($modules as $mod):
            // Étudiants inscrits à ce module
            $stmt = $pdo->prepare("
                SELECT e.id, e.nom, e.prenom, e.matricule, n.note
                FROM inscriptions i
                JOIN etudiants e ON e.id = i.etudiant_id
                LEFT JOIN notes n ON n.etudiant_id = e.id AND n.module_id = i.module_id AND n.annee_univ = '2025/2026'
                WHERE i.module_id = ? AND i.annee_univ = '2025/2026'
                ORDER BY e.nom, e.prenom
            ");
            $stmt->execute([$mod['id']]);
            $inscrits = $stmt->fetchAll();
        ?>
        <div class="card">
            <div class="card-header">
                <div class="card-title"><?= h($mod['code']) ?> – <?= h($mod['intitule']) ?></div>
                <span class="badge b-blue">Coeff. <?= h($mod['coefficient']) ?></span>
            </div>
            <?php if (empty($inscrits)): ?>
                <div style="font-size:12px;color:var(--color-text-secondary);padding:8px 0;">Aucun étudiant inscrit.</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr><th>Matricule</th><th>Nom & Prénom</th><th>Note actuelle</th><th>Saisir / Modifier</th></tr>
                </thead>
                <tbody>
                <?php foreach ($inscrits as $ins): ?>
                    <tr>
                        <td><?= h($ins['matricule']) ?></td>
                        <td><?= h($ins['nom'] . ' ' . $ins['prenom']) ?></td>
                        <td>
                            <?php if ($ins['note'] !== null): ?>
                                <span style="font-weight:600; color:<?= $ins['note'] >= 10 ? 'var(--color-green)' : 'var(--color-red)' ?>">
                                    <?= number_format($ins['note'], 2) ?>
                                </span>
                            <?php else: ?>
                                <span style="color:var(--color-text-tertiary);">–</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="?panel=notes" style="display:flex;gap:6px;align-items:center;">
                                <input type="hidden" name="etudiant_id" value="<?= $ins['id'] ?>">
                                <input type="hidden" name="module_id"   value="<?= $mod['id'] ?>">
                                <input type="number" name="note_val" step="0.25" min="0" max="20"
                                    value="<?= $ins['note'] !== null ? number_format($ins['note'], 2) : '' ?>"
                                    placeholder="0–20" style="width:80px;padding:5px 8px;border:1px solid var(--color-border);border-radius:6px;font-size:12px;">
                                <button type="submit" name="save_note" class="btn-blue">Sauvegarder</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

    <?php elseif ($panel === 'modules'): ?>
        <!-- ── Panel : Mes modules ── -->
        <div class="page-head">
            <div class="page-title">Mes modules</div>
            <div class="page-sub">Modules assignés – <?= APP_YEAR ?></div>
        </div>

        <div class="card">
            <?php if (empty($modules)): ?>
                <div style="font-size:12px;color:var(--color-text-secondary);">Aucun module assigné pour cette année.</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr><th>Code</th><th>Intitulé</th><th>Coeff.</th><th>Niveau</th><th>Étudiants inscrits</th><th>Moyenne classe</th></tr>
                </thead>
                <tbody>
                <?php foreach ($modules as $mod):
                    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM inscriptions WHERE module_id = ? AND annee_univ = '2025/2026'");
                    $stmt->execute([$mod['id']]);
                    $cnt = $stmt->fetchColumn();

                    $stmt = $pdo->prepare("SELECT AVG(note) FROM notes WHERE module_id = ? AND annee_univ = '2025/2026'");
                    $stmt->execute([$mod['id']]);
                    $avg = $stmt->fetchColumn();
                    $avg_str = $avg ? number_format($avg, 2) : '–';
                    $avg_color = $avg ? ($avg >= 10 ? 'var(--color-green)' : 'var(--color-red)') : 'var(--color-text-secondary)';
                ?>
                <tr>
                    <td><?= h($mod['code']) ?></td>
                    <td><?= h($mod['intitule']) ?></td>
                    <td><?= h($mod['coefficient']) ?></td>
                    <td><?= h($mod['niveau']) ?></td>
                    <td><?= $cnt ?></td>
                    <td style="font-weight:600;color:<?= $avg_color ?>"><?= $avg_str ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    <?php elseif ($panel === 'profil'): ?>
        <!-- ── Panel : Profil ── -->
        <div class="page-head">
            <div class="page-title">Mon profil</div>
            <div class="page-sub">Informations personnelles</div>
        </div>

        <div class="card" style="max-width:520px;">
            <div class="card-header">
                <div style="display:flex;align-items:center;gap:14px;">
                    <div class="avatar av-green" style="width:48px;height:48px;font-size:16px;font-weight:700;"><?= h($initials) ?></div>
                    <div>
                        <div style="font-size:16px;font-weight:600;"><?= h($ens['grade'] . ' ' . $ens['prenom'] . ' ' . $ens['nom']) ?></div>
                        <div style="font-size:12px;color:var(--color-text-secondary);"><?= h($ens['departement']) ?></div>
                    </div>
                </div>
            </div>
            <div class="info-row"><span class="info-key">Email</span><span class="info-val"><?= h($ens['email']) ?></span></div>
            <div class="info-row"><span class="info-key">Grade</span><span class="info-val"><?= h($ens['grade']) ?></span></div>
            <div class="info-row"><span class="info-key">Département</span><span class="info-val"><?= h($ens['departement']) ?></span></div>
            <?php if ($ens['specialite']): ?>
            <div class="info-row"><span class="info-key">Spécialité</span><span class="info-val"><?= h($ens['specialite']) ?></span></div>
            <?php endif; ?>
            <div class="info-row"><span class="info-key">Modules assignés</span><span class="info-val"><?= count($modules) ?></span></div>
        </div>
    <?php endif; ?>

    </div><!-- /main-content -->
</div><!-- /dash-layout -->

</body>
</html>
