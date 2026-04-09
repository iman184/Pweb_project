<?php
// ============================================================
//  pages/dashboard_etudiant.php — Espace Étudiant
// ============================================================
$user_id = $_SESSION['user_id'];
$pdo     = get_pdo();

// Infos étudiant
$stmt = $pdo->prepare("SELECT * FROM etudiants WHERE id = ?");
$stmt->execute([$user_id]);
$etudiant = $stmt->fetch();

// Notes
$stmt = $pdo->prepare("
    SELECT m.code, m.intitule, m.coefficient, n.note
    FROM inscriptions i
    JOIN modules m ON m.id = i.module_id
    LEFT JOIN notes n ON n.etudiant_id = i.etudiant_id AND n.module_id = i.module_id AND n.annee_univ = i.annee_univ
    WHERE i.etudiant_id = ? AND i.annee_univ = '2025/2026'
    ORDER BY m.code
");
$stmt->execute([$user_id]);
$notes = $stmt->fetchAll();

// Calcul de la moyenne pondérée
$total_coeff = 0; $total_note = 0; $admis = 0; $ajournes = 0;
foreach ($notes as $n) {
    if ($n['note'] !== null) {
        $total_coeff += $n['coefficient'];
        $total_note  += $n['note'] * $n['coefficient'];
        if ($n['note'] >= 10) $admis++; else $ajournes++;
    }
}
$moyenne = $total_coeff > 0 ? round($total_note / $total_coeff, 2) : null;

// Panel actif
$panel = $_GET['panel'] ?? 'notes';
$panels = ['notes', 'profil'];

// Initiales pour l'avatar
$initials = strtoupper(substr($etudiant['prenom'], 0, 1) . substr($etudiant['nom'], 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Étudiant – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-logo">
        <div class="logo-box blue"><span>FI</span></div>
        <div>
            <div class="brand"><?= APP_NAME ?></div>
            <div class="brand-sub">Espace étudiant</div>
        </div>
    </div>
    <div class="topbar-right">
        <div class="avatar av-blue"><?= h($initials) ?></div>
        <div>
            <div class="uname"><?= h($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></div>
            <div class="urole">Étudiant · <?= h($etudiant['niveau']) ?></div>
        </div>
        <a href="logout.php"><button class="btn-sm">Déconnexion</button></a>
    </div>
</div>

<div class="dash-layout">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="nav-section">Mon espace</div>
        <a href="?panel=notes" class="nav-item <?= $panel === 'notes' ? 'active-blue' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <path d="M3 3h10v10H3z" fill="none" stroke="currentColor" stroke-width="1.4"/>
                <path d="M6 7l2 2 4-4" stroke="currentColor" stroke-width="1.4" fill="none"/>
            </svg>
            Mes notes
        </a>
        <a href="releve%20de%20note.php" class="nav-item" target="_blank" rel="noopener noreferrer">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <path d="M4 3h8v10H4z" fill="none" stroke="currentColor" stroke-width="1.4"/>
                <path d="M6 5h4M6 8h4M6 11h2" stroke="currentColor" stroke-width="1.4" fill="none"/>
            </svg>
            Relevé de note
        </a>
        <a href="?panel=profil" class="nav-item <?= $panel === 'profil' ? 'active-blue' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="5" r="3" fill="none" stroke="currentColor" stroke-width="1.4"/>
                <path d="M2 14c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="currentColor" stroke-width="1.4" fill="none"/>
            </svg>
            Mon profil
        </a>
    </div>

    <!-- Contenu principal -->
    <div class="main-content">

        <?php if ($panel === 'notes'): ?>
        <!-- ── Panel : Notes ── -->
        <div class="page-head">
            <div class="page-title">Mes notes – <?= APP_YEAR ?></div>
            <div class="page-sub">Résultats académiques, <?= h($etudiant['niveau']) ?></div>
        </div>

        <!-- Stats -->
        <div class="stat-grid sg4" style="margin-bottom:18px;">
            <div class="stat-card">
                <div class="stat-card-label">Moyenne générale</div>
                <div class="stat-card-val val-blue"><?= $moyenne !== null ? number_format($moyenne, 2) : '–' ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Modules inscrits</div>
                <div class="stat-card-val val-blue"><?= count($notes) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Modules admis</div>
                <div class="stat-card-val val-green"><?= $admis ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Modules ajournés</div>
                <div class="stat-card-val val-red"><?= $ajournes ?></div>
            </div>
        </div>

        <!-- Tableau des notes -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Relevé de notes</div>
                <span class="badge b-blue"><?= APP_YEAR ?></span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Code</th><th>Module</th><th>Coeff.</th><th>Note /20</th><th>Résultat</th><th>Progression</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($notes as $n): ?>
                    <?php
                    $note = $n['note'];
                    $pct  = $note !== null ? round($note / 20 * 100) : 0;
                    $color = $note === null ? '#9CA3AF' : ($note >= 10 ? '#3B6D11' : '#A32D2D');
                    $badge = $note === null ? '<span class="badge b-mid">–</span>'
                           : ($note >= 10 ? '<span class="badge b-ok">Admis</span>' : '<span class="badge b-no">Ajourné</span>');
                    ?>
                    <tr>
                        <td><?= h($n['code']) ?></td>
                        <td><?= h($n['intitule']) ?></td>
                        <td><?= h($n['coefficient']) ?></td>
                        <td style="font-weight:600;color:<?= $color ?>"><?= $note !== null ? number_format($note, 2) : '–' ?></td>
                        <td><?= $badge ?></td>
                        <td style="min-width:100px;">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($moyenne !== null): ?>
            <div style="margin-top:16px; padding-top:12px; border-top:1px solid var(--color-border-light); display:flex; align-items:center; gap:12px;">
                <div class="moy-big" style="padding:0; font-size:26px;"><?= number_format($moyenne, 2) ?></div>
                <div>
                    <div style="font-size:13px;font-weight:600;">Moyenne pondérée</div>
                    <div style="font-size:11px;color:var(--color-text-secondary);">
                        <?= $moyenne >= 10 ? '✓ Semestre validé' : '✗ Semestre non validé' ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php elseif ($panel === 'profil'): ?>
        <!-- ── Panel : Profil ── -->
        <div class="page-head">
            <div class="page-title">Mon profil</div>
            <div class="page-sub">Informations personnelles et académiques</div>
        </div>

        <div class="card" style="max-width:520px;">
            <div class="card-header">
                <div style="display:flex;align-items:center;gap:14px;">
                    <div class="avatar av-blue" style="width:48px;height:48px;font-size:16px;font-weight:700;"><?= h($initials) ?></div>
                    <div>
                        <div style="font-size:16px;font-weight:600;"><?= h($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></div>
                        <div style="font-size:12px;color:var(--color-text-secondary);">Étudiant · <?= h($etudiant['niveau']) ?></div>
                    </div>
                </div>
            </div>
            <div class="info-row"><span class="info-key">Matricule</span><span class="info-val"><?= h($etudiant['matricule']) ?></span></div>
            <div class="info-row"><span class="info-key">Email</span><span class="info-val"><?= h($etudiant['email']) ?></span></div>
            <div class="info-row"><span class="info-key">Niveau</span><span class="info-val"><?= h($etudiant['niveau']) ?></span></div>
            <?php if ($etudiant['date_naissance']): ?>
            <div class="info-row">
                <span class="info-key">Date de naissance</span>
                <span class="info-val"><?= date('d/m/Y', strtotime($etudiant['date_naissance'])) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row"><span class="info-key">Année universitaire</span><span class="info-val"><?= APP_YEAR ?></span></div>
            <div class="info-row"><span class="info-key">Inscrit le</span><span class="info-val"><?= date('d/m/Y', strtotime($etudiant['created_at'])) ?></span></div>
        </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
