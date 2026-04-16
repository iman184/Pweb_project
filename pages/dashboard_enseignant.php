<?php
// ============================================================
//  dashboard_enseignant.php — Teacher Dashboard (complet)
//  Loaded by dashboard.php — session & auth already handled
// ============================================================

$user_id = (int)$_SESSION['user_id'];
$pdo     = get_pdo();

// Helper: escape HTML (define only if not already defined)
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Helper: grade color class
function noteClass($v) {
    if ($v === null) return '';
    if ($v < 10)  return 'val-red';
    if ($v < 15)  return 'val-amber';
    return 'val-green';
}
function noteBadge($v) {
    if ($v === null) return '';
    if ($v < 10)  return 'b-no';
    if ($v < 15)  return 'b-mid';
    return 'b-ok';
}

// --- Load teacher info ---
$stmt = $pdo->prepare("SELECT * FROM enseignants WHERE id = ?");
$stmt->execute([$user_id]);
$ens = $stmt->fetch();
if (!$ens) { header('Location: login.php'); exit; }

// --- Load teacher's modules ---
$stmt = $pdo->prepare("SELECT * FROM modules WHERE enseignant_id = ? AND annee_univ = ?");
$stmt->execute([$user_id, APP_YEAR]);
$modules = $stmt->fetchAll();
$module = $modules[0] ?? null;
$modules = $module ? [$module] : [];

$initials = strtoupper(substr($ens['prenom'], 0, 1) . substr($ens['nom'], 0, 1));
$allowed_panels = ['modules', 'notes', 'etudiants', 'resultats'];
$panel    = $_GET['panel'] ?? 'modules';
if (!in_array($panel, $allowed_panels, true)) {
    $panel = 'modules';
}
$notif    = '';

// ============================================================
//  POST — Save grades (td / tp / exam)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_note'])) {
    $etudiant_id = (int)$_POST['etudiant_id'];
    $module_id   = (int)$_POST['module_id'];
    $td   = $_POST['td']   !== '' ? (float)str_replace(',','.',$_POST['td'])   : null;
    $tp   = $_POST['tp']   !== '' ? (float)str_replace(',','.',$_POST['tp'])   : null;
    $exam = $_POST['exam'] !== '' ? (float)str_replace(',','.',$_POST['exam']) : null;

    $err = false;
    foreach ([$td, $tp, $exam] as $n) {
        if ($n !== null && ($n < 0 || $n > 20)) { $err = true; break; }
    }

    if ($err) {
        $notif = '<div class="notif notif-err">Invalid grade — each grade must be between 0 and 20.</div>';
    } else {
        // Verify module belongs to this teacher
        $chk = $pdo->prepare("SELECT id FROM modules WHERE id = ? AND enseignant_id = ?");
        $chk->execute([$module_id, $user_id]);
        if ($chk->fetch()) {
            // Calculate moyenne (exclude null values)
            $parts = array_filter([$td, $tp, $exam], fn($x) => $x !== null);
            $moy   = count($parts) > 0 ? array_sum($parts) / count($parts) : null;

            // Check if student is excluded (5+ absences in TD or TP)
            $abs_td = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE etudiant_id=? AND module_id=? AND type='td' AND statut='A' AND annee_univ=?");
            $abs_td->execute([$etudiant_id, $module_id, APP_YEAR]);
            $abs_tp = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE etudiant_id=? AND module_id=? AND type='tp' AND statut='A' AND annee_univ=?");
            $abs_tp->execute([$etudiant_id, $module_id, APP_YEAR]);

            if ($abs_td->fetchColumn() >= 5) $td = 0;
            if ($abs_tp->fetchColumn() >= 5) $tp = 0;

            // Recalc after exclusion
            $parts2 = array_filter([$td, $tp, $exam], fn($x) => $x !== null);
            $moy    = count($parts2) > 0 ? array_sum($parts2) / count($parts2) : null;

            $stmt = $pdo->prepare("
                INSERT INTO notes (etudiant_id, module_id, td, tp, exam, moyenne, note, annee_univ)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE td=VALUES(td), tp=VALUES(tp), exam=VALUES(exam), moyenne=VALUES(moyenne), note=VALUES(note)
            ");
            $stmt->execute([$etudiant_id, $module_id, $td, $tp, $exam, $moy, $moy, APP_YEAR]);
            $notif = '<div class="notif notif-ok">&#10003; Grade saved successfully.</div>';
        }
    }
}

// ============================================================
//  Attendance exclusion threshold used for grade rules
// ============================================================
$abs_limit     = 5;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard – <?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/teacher.css">
</head>
<body>

<!-- ══ TOPBAR ══════════════════════════════════════════════ -->
<div class="topbar">
    <div class="topbar-logo">
        <img src="IMG\5855047178127084746_109.jpg" alt="USTHB" style="height:36px;width:auto;object-fit:contain;">
        <div>
            <div class="brand"><?= h(APP_NAME) ?></div>
            <div class="brand-sub">Teacher Space</div>
        </div>
    </div>
    <div class="topbar-right">
        <div class="avatar av-green"><?= h($initials) ?></div>
        <div>
            <div class="uname"><?= h($ens['grade'] . ' ' . $ens['nom']) ?></div>
            <div class="urole">Teacher · <?= h($ens['departement']) ?></div>
        </div>
    </div>
</div>

<div class="dash-layout">

    <!-- ══ SIDEBAR ══════════════════════════════════════════ -->
    <div class="sidebar">
        <div class="nav-section">MY SPACE</div>

        <a href="dashboard.php?panel=modules" class="nav-item <?= $panel === 'modules' ? 'active-green' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <rect x="2" y="3" width="12" height="11" rx="1.5" stroke="currentColor" stroke-width="1.3" fill="none"/>
                <path d="M5 1v3M11 1v3M2 7h12" stroke="currentColor" stroke-width="1.3"/>
            </svg>
            My Modules
        </a>
        <a href="dashboard.php?panel=notes" class="nav-item <?= $panel === 'notes' ? 'active-green' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <path d="M3 3h10v10H3z" stroke="currentColor" stroke-width="1.4" fill="none"/>
                <path d="M6 7l2 2 4-4" stroke="currentColor" stroke-width="1.4" fill="none"/>
            </svg>
            Enter / Edit Grades
        </a>
        <a href="dashboard.php?panel=etudiants" class="nav-item <?= $panel === 'etudiants' ? 'active-green' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <circle cx="6" cy="5" r="2.5" stroke="currentColor" stroke-width="1.3" fill="none"/>
                <path d="M1 14c0-2.8 2.2-5 5-5s5 2.2 5 5" stroke="currentColor" stroke-width="1.3" fill="none"/>
                <path d="M12 7l1.5 1.5L16 6" stroke="currentColor" stroke-width="1.3" fill="none"/>
            </svg>
            Students
        </a>

        <a href="dashboard.php?panel=resultats" class="nav-item <?= $panel === 'resultats' ? 'active-green' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <path d="M2 13l3-4 3 2 3-5 3 3" stroke="currentColor" stroke-width="1.4" fill="none"/>
            </svg>
            Send Results
        </a>

        <a href="logout.php" class="nav-item" style="margin-top:auto;color:var(--color-red);border-top:1px solid var(--color-border-light);">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <path d="M6 2H2v12h4M11 11l3-3-3-3M6 8h8" stroke="currentColor" stroke-width="1.4" fill="none"/>
            </svg>
            Logout
        </a>
    </div>

    <!-- ══ MAIN CONTENT ═════════════════════════════════════ -->
    <div class="main-content">

    <?= $notif ?>

    <!-- ════════════════════════════════════════════════════
         PANEL : HOME
    ════════════════════════════════════════════════════ -->
    <?php if ($panel === 'accueil'):
        // Gather stats
        $all_avgs  = [];
        $at_risk   = [];
        $excl_risk = [];
        foreach ($modules as $mod) {
            $s = $pdo->prepare("
                SELECT e.id, e.nom, e.prenom, e.matricule, n.moyenne
                FROM inscriptions i
                JOIN etudiants e ON e.id = i.etudiant_id
                LEFT JOIN notes n ON n.etudiant_id = e.id AND n.module_id = ? AND n.annee_univ = ?
                WHERE i.module_id = ? AND i.annee_univ = ?
            ");
            $s->execute([$mod['id'], APP_YEAR, $mod['id'], APP_YEAR]);
            foreach ($s->fetchAll() as $r) {
                if ($r['moyenne'] !== null) $all_avgs[] = (float)$r['moyenne'];
                if ($r['moyenne'] !== null && $r['moyenne'] < 10)
                    $at_risk[] = ['nom'=>$r['nom'].' '.$r['prenom'], 'mat'=>$r['matricule'], 'moy'=>$r['moyenne'], 'mod'=>$mod['code']];
                $atd = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE etudiant_id=? AND module_id=? AND type='td' AND statut='A' AND annee_univ=?");
                $atd->execute([$r['id'], $mod['id'], APP_YEAR]);
                $atp = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE etudiant_id=? AND module_id=? AND type='tp' AND statut='A' AND annee_univ=?");
                $atp->execute([$r['id'], $mod['id'], APP_YEAR]);
                $nbtd = (int)$atd->fetchColumn(); $nbtp = (int)$atp->fetchColumn();
                if ($nbtd >= 3 || $nbtp >= 3)
                    $excl_risk[] = ['nom'=>$r['nom'].' '.$r['prenom'], 'mat'=>$r['matricule'], 'td_abs'=>$nbtd, 'tp_abs'=>$nbtp, 'mod'=>$mod['code']];
            }
        }
        $total      = count($all_avgs);
        $class_avg  = $total > 0 ? array_sum($all_avgs) / $total : null;
        $nb_good    = count(array_filter($all_avgs, fn($v) => $v >= 15));
        $nb_avg     = count(array_filter($all_avgs, fn($v) => $v >= 10 && $v < 15));
        $nb_fail    = count(array_filter($all_avgs, fn($v) => $v < 10));
    ?>

    <div class="page-head">
        <div class="page-title">Welcome, <?= h($ens['grade'] . ' ' . $ens['nom']) ?></div>
        <div class="page-sub"><?= h(APP_YEAR) ?> · <?= h($ens['departement']) ?></div>
    </div>

    <!-- Stat cards -->
    <div class="stat-grid sg4" style="margin-bottom:20px">
        <div class="stat-card">
            <div class="stat-card-label">MODULES</div>
            <div class="stat-card-val val-blue"><?= count($modules) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">STUDENTS</div>
            <div class="stat-card-val val-blue"><?= $total ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">CLASS AVERAGE</div>
            <div class="stat-card-val <?= $class_avg !== null ? noteClass($class_avg) : '' ?>">
                <?= $class_avg !== null ? number_format($class_avg, 2) : '—' ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-label">PASS / FAIL</div>
            <div class="stat-card-val">
                <span class="val-green"><?= $nb_good + $nb_avg ?></span>
                <span style="font-size:14px;color:var(--color-text-tertiary)"> / </span>
                <span class="val-red"><?= $nb_fail ?></span>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

        <!-- Grade distribution chart -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Grade Distribution</div>
                <span class="badge b-blue"><?= $total ?> grades</span>
            </div>
            <?php if ($total === 0): ?>
                <div style="font-size:12px;color:var(--color-text-secondary)">No grades entered yet.</div>
            <?php else: ?>
            <div class="chart-wrap">
                <div class="chart-row">
                    <div class="chart-label">15 – 20</div>
                    <div class="chart-bar-bg">
                        <div class="chart-bar green" style="width:<?= $total > 0 ? round($nb_good/$total*100) : 0 ?>%"></div>
                    </div>
                    <div class="chart-count val-green"><?= $nb_good ?> student<?= $nb_good !== 1 ? 's' : '' ?></div>
                </div>
                <div class="chart-row">
                    <div class="chart-label">10 – 14</div>
                    <div class="chart-bar-bg">
                        <div class="chart-bar amber" style="width:<?= $total > 0 ? round($nb_avg/$total*100) : 0 ?>%"></div>
                    </div>
                    <div class="chart-count val-amber"><?= $nb_avg ?> student<?= $nb_avg !== 1 ? 's' : '' ?></div>
                </div>
                <div class="chart-row">
                    <div class="chart-label">0 – 9</div>
                    <div class="chart-bar-bg">
                        <div class="chart-bar red" style="width:<?= $total > 0 ? round($nb_fail/$total*100) : 0 ?>%"></div>
                    </div>
                    <div class="chart-count val-red"><?= $nb_fail ?> student<?= $nb_fail !== 1 ? 's' : '' ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Near exclusion -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Exclusion Risk</div>
                <span class="badge b-mid"><?= count($excl_risk) ?></span>
            </div>
            <?php if (empty($excl_risk)): ?>
                <div style="font-size:12px;color:var(--color-text-secondary)">No students at risk ✓</div>
            <?php else: ?>
            <table>
                <thead><tr><th>Student</th><th>Module</th><th>TD Abs</th><th>TP Abs</th></tr></thead>
                <tbody>
                <?php foreach ($excl_risk as $r): ?>
                <tr>
                    <td><?= h($r['nom']) ?><br><span style="font-size:10px;color:var(--color-text-tertiary)"><?= h($r['mat']) ?></span></td>
                    <td><span class="badge b-blue"><?= h($r['mod']) ?></span></td>
                    <td><span class="<?= $r['td_abs'] >= 5 ? 'val-red' : 'val-amber' ?>" style="font-weight:700"><?= $r['td_abs'] ?>/5</span></td>
                    <td><span class="<?= $r['tp_abs'] >= 5 ? 'val-red' : 'val-amber' ?>" style="font-weight:700"><?= $r['tp_abs'] ?>/5</span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div>

    <!-- Failing students -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Failing Students</div>
            <span class="badge b-no"><?= count($at_risk) ?></span>
        </div>
        <?php if (empty($at_risk)): ?>
            <div style="font-size:12px;color:var(--color-text-secondary)">No failing students ✓</div>
        <?php else: ?>
        <table>
            <thead><tr><th>Student</th><th>Student ID</th><th>Module</th><th>Average</th></tr></thead>
            <tbody>
            <?php foreach ($at_risk as $r): ?>
            <tr>
                <td style="font-weight:600"><?= h($r['nom']) ?></td>
                <td style="font-family:monospace;font-size:11px"><?= h($r['mat']) ?></td>
                <td><span class="badge b-blue"><?= h($r['mod']) ?></span></td>
                <td><span class="val-red" style="font-weight:700"><?= number_format($r['moy'],2) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ════════════════════════════════════════════════════
         PANEL : GRADE ENTRY
    ════════════════════════════════════════════════════ -->
    <?php elseif ($panel === 'notes'): ?>

    <div class="page-head">
        <div class="page-title">Grade Entry</div>
        <div class="page-sub">TD · TP · Exam — average calculated automatically</div>
    </div>

    <?php foreach ($modules as $mod):
        $stmt = $pdo->prepare("
            SELECT e.id, e.nom, e.prenom, e.matricule,
                   n.td, n.tp, n.exam, n.moyenne
            FROM inscriptions i
            JOIN etudiants e ON e.id = i.etudiant_id
            LEFT JOIN notes n ON n.etudiant_id = e.id
                AND n.module_id = ? AND n.annee_univ = ?
            WHERE i.module_id = ? AND i.annee_univ = ?
            ORDER BY e.nom, e.prenom
        ");
        $stmt->execute([$mod['id'], APP_YEAR, $mod['id'], APP_YEAR]);
        $inscrits = $stmt->fetchAll();
    ?>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= h($mod['code']) ?> — <?= h($mod['intitule']) ?></div>
            <span class="badge b-blue">Coeff. <?= h($mod['coefficient']) ?></span>
        </div>

        <?php if (empty($inscrits)): ?>
            <div style="font-size:12px;color:var(--color-text-secondary)">No enrolled students.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>TD /20</th>
                    <th>TP /20</th>
                    <th>Exam /20</th>
                    <th>Average</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($inscrits as $ins):
                // Check exclusion
                $abs_td = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE etudiant_id=? AND module_id=? AND type='td' AND statut='A' AND annee_univ=?");
                $abs_td->execute([$ins['id'], $mod['id'], APP_YEAR]);
                $excl_td = $abs_td->fetchColumn() >= $abs_limit;

                $abs_tp = $pdo->prepare("SELECT COUNT(*) FROM absences WHERE etudiant_id=? AND module_id=? AND type='tp' AND statut='A' AND annee_univ=?");
                $abs_tp->execute([$ins['id'], $mod['id'], APP_YEAR]);
                $excl_tp = $abs_tp->fetchColumn() >= $abs_limit;
            ?>
            <tr>
                <td><?= h($ins['matricule']) ?></td>
                <td><?= h($ins['nom'] . ' ' . $ins['prenom']) ?></td>

                <form method="POST" action="dashboard.php?panel=notes" style="display:contents">
                    <input type="hidden" name="etudiant_id" value="<?= $ins['id'] ?>">
                    <input type="hidden" name="module_id"   value="<?= $mod['id'] ?>">

                    <td>
                        <?php if ($excl_td): ?>
                            <span class="badge b-no" title="Excludedded (≥5 TD absences)">0 — Excluded</span>
                            <input type="hidden" name="td" value="0">
                        <?php else: ?>
                            <input class="note-input" type="number" name="td"
                                min="0" max="20" step="0.25"
                                value="<?= $ins['td'] ?? '' ?>"
                                placeholder="—">
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($excl_tp): ?>
                            <span class="badge b-no" title="Excludedded (≥5 TP absences)">0 — Excluded</span>
                            <input type="hidden" name="tp" value="0">
                        <?php else: ?>
                            <input class="note-input" type="number" name="tp"
                                min="0" max="20" step="0.25"
                                value="<?= $ins['tp'] ?? '' ?>"
                                placeholder="—">
                        <?php endif; ?>
                    </td>

                    <td>
                        <input class="note-input" type="number" name="exam"
                            min="0" max="20" step="0.25"
                            value="<?= $ins['exam'] ?? '' ?>"
                            placeholder="—">
                    </td>

                    <td style="font-weight:600">
                        <?php if ($ins['moyenne'] !== null): ?>
                            <span class="<?= noteClass($ins['moyenne']) ?>">
                                <?= number_format($ins['moyenne'], 2) ?>
                            </span>
                        <?php else: ?>
                            <span style="color:var(--color-text-tertiary)">—</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <button type="submit" name="save_note" class="btn-blue">Apply</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php endforeach; ?>


    <!-- ════════════════════════════════════════════════════
         PANEL : ABSENCES
    ════════════════════════════════════════════════════ -->
    <?php elseif ($panel === 'absences'): ?>

    <div class="page-head">
        <div class="page-title">Attendance</div>
        <div class="page-sub">Mark present (P) or absent (A) — click a button to toggle</div>
    </div>

    <?php if (empty($modules)): ?>
        <div class="card"><div style="font-size:12px;color:var(--color-text-secondary)">No module assigned.</div></div>
    <?php else: ?>

    <!-- Module selector -->
    <div class="card" style="padding:12px 16px;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <span style="font-size:11px;color:var(--color-text-secondary);font-weight:700">MODULE:</span>
            <?php foreach ($modules as $m): ?>
            <a href="dashboard.php?panel=absences&module_id=<?= $m['id'] ?>&type=<?= h($att_type) ?>"
               class="type-btn <?= $m['id'] == $att_module_id ? ($att_type==='td' ? 'active-td' : 'active-tp') : '' ?>">
               <?= h($m['code']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TD / TP toggle -->
    <div class="type-switch">
        <a href="dashboard.php?panel=absences&module_id=<?= $att_module_id ?>&type=td"
           class="type-btn <?= $att_type === 'td' ? 'active-td' : '' ?>">TD</a>
        <a href="dashboard.php?panel=absences&module_id=<?= $att_module_id ?>&type=tp"
           class="type-btn <?= $att_type === 'tp' ? 'active-tp' : '' ?>">TP</a>
    </div>

    <?php
    // Load students for this module
    $stmt = $pdo->prepare("
        SELECT e.id, e.nom, e.prenom, e.matricule
        FROM inscriptions i
        JOIN etudiants e ON e.id = i.etudiant_id
        WHERE i.module_id = ? AND i.annee_univ = ?
        ORDER BY e.nom, e.prenom
    ");
    $stmt->execute([$att_module_id, APP_YEAR]);
    $att_students = $stmt->fetchAll();

    // Load existing attendance for all students at once
    $stmt = $pdo->prepare("
        SELECT etudiant_id, session_num, statut
        FROM absences
        WHERE module_id = ? AND type = ? AND annee_univ = ?
    ");
    $stmt->execute([$att_module_id, $att_type, APP_YEAR]);
    $att_data = [];
    foreach ($stmt->fetchAll() as $row) {
        $att_data[$row['etudiant_id']][$row['session_num']] = $row['statut'];
    }
    ?>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <?= h($att_type === 'td' ? 'Travaux Dirigés (TD)' : 'Travaux Pratiques (TP)') ?>
                — <?= $att_sessions ?> séances
            </div>
            <span class="badge b-no">Excluded si ≥ <?= $abs_limit ?> absences</span>
        </div>

        <?php if (empty($att_students)): ?>
            <div style="font-size:12px;color:var(--color-text-secondary)">No enrolled students.</div>
        <?php else: ?>

        <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <?php for ($s = 1; $s <= $att_sessions; $s++): ?>
                    <th>S<?= $s ?></th>
                    <?php endfor; ?>
                    <th>Attendance</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($att_students as $stu):
                $abs_count = 0;
                for ($s = 1; $s <= $att_sessions; $s++) {
                    if (($att_data[$stu['id']][$s] ?? 'P') === 'A') $abs_count++;
                }
                $excluded = ($abs_count >= $abs_limit);
            ?>
            <tr class="<?= $excluded ? 'row-excluded' : '' ?>">
                <td><?= h($stu['matricule']) ?></td>
                <td><?= h($stu['nom'] . ' ' . $stu['prenom']) ?></td>

                <?php for ($s = 1; $s <= $att_sessions; $s++):
                    $cur = $att_data[$stu['id']][$s] ?? 'P';
                    $next = ($cur === 'P') ? 'A' : 'P';
                ?>
                <td style="padding:5px;text-align:center">
                    <form method="POST" action="dashboard.php?panel=absences&module_id=<?= $att_module_id ?>&type=<?= $att_type ?>" style="display:inline">
                        <input type="hidden" name="save_abs"     value="1">
                        <input type="hidden" name="etudiant_id"  value="<?= $stu['id'] ?>">
                        <input type="hidden" name="module_id"    value="<?= $att_module_id ?>">
                        <input type="hidden" name="abs_type"     value="<?= $att_type ?>">
                        <input type="hidden" name="session_num"  value="<?= $s ?>">
                        <input type="hidden" name="statut"       value="<?= $next ?>">
                        <button type="submit" class="att-btn <?= $cur ?>"><?= $cur ?></button>
                    </form>
                </td>
                <?php endfor; ?>

                <td style="font-weight:700;text-align:center">
                    <span class="<?= $abs_count >= $abs_limit ? 'val-red' : ($abs_count >= 3 ? 'val-amber' : 'val-green') ?>">
                        <?= $abs_count ?>
                    </span>
                </td>
                <td>
                    <?php if ($excluded): ?>
                        <span class="badge b-no">Excluded</span>
                    <?php else: ?>
                        <span class="badge b-ok">Active</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div style="margin-top:10px;font-size:11px;color:var(--color-text-secondary)">
            Click a <strong>P</strong> or <strong>A</strong> button to toggle attendance.
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>


    <!-- ════════════════════════════════════════════════════
         PANEL : ÉTUDIANTS
    ════════════════════════════════════════════════════ -->
    <?php elseif ($panel === 'etudiants'): ?>

    <div class="page-head">
        <div class="page-title">Students</div>
        <div class="page-sub">Students enrolled in your modules</div>
    </div>

    <?php foreach ($modules as $mod):
        $stmt = $pdo->prepare("
            SELECT e.matricule, e.nom, e.prenom, e.email, e.niveau,
                   n.moyenne
            FROM inscriptions i
            JOIN etudiants e ON e.id = i.etudiant_id
            LEFT JOIN notes n ON n.etudiant_id = e.id AND n.module_id = ? AND n.annee_univ = ?
            WHERE i.module_id = ? AND i.annee_univ = ?
            ORDER BY e.nom, e.prenom
        ");
        $stmt->execute([$mod['id'], APP_YEAR, $mod['id'], APP_YEAR]);
        $etuds = $stmt->fetchAll();
    ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= h($mod['code']) ?> — <?= h($mod['intitule']) ?></div>
            <span class="badge b-blue"><?= count($etuds) ?>  students</span>
        </div>
        <?php if (empty($etuds)): ?>
            <div style="font-size:12px;color:var(--color-text-secondary)">No students.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr><th>Student ID</th><th>Full Name</th><th>Level</th><th>Email</th><th>Average</th></tr>
            </thead>
            <tbody>
            <?php foreach ($etuds as $e): ?>
            <tr>
                <td><?= h($e['matricule']) ?></td>
                <td><?= h($e['nom'] . ' ' . $e['prenom']) ?></td>
                <td><?= h($e['niveau']) ?></td>
                <td style="font-size:11px;color:var(--color-text-secondary)"><?= h($e['email']) ?></td>
                <td style="font-weight:600">
                    <?php if ($e['moyenne'] !== null): ?>
                        <span class="<?= noteClass($e['moyenne']) ?>"><?= number_format($e['moyenne'], 2) ?></span>
                    <?php else: ?>
                        <span style="color:var(--color-text-tertiary)">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>


    <!-- ════════════════════════════════════════════════════
         PANEL : MES MODULES
    ════════════════════════════════════════════════════ -->
    <?php elseif ($panel === 'modules'): ?>

    <div class="page-head">
        <div class="page-title">My Modules</div>
        <div class="page-sub">Assigned modules — <?= h(APP_YEAR) ?></div>
    </div>

    <div class="card">
        <?php if (empty($modules)): ?>
            <div style="font-size:12px;color:var(--color-text-secondary)">No module assigned.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr><th>Code</th><th>Title</th><th>Coeff.</th><th>Level</th><th>Enrolled</th><th>Class Avg</th></tr>
            </thead>
            <tbody>
            <?php foreach ($modules as $mod):
                $cnt = $pdo->prepare("SELECT COUNT(*) FROM inscriptions WHERE module_id=? AND annee_univ=?");
                $cnt->execute([$mod['id'], APP_YEAR]);
                $nb_etuds = $cnt->fetchColumn();

                $avg_q = $pdo->prepare("SELECT AVG(moyenne) FROM notes WHERE module_id=? AND annee_univ=?");
                $avg_q->execute([$mod['id'], APP_YEAR]);
                $cls_avg = $avg_q->fetchColumn();
            ?>
            <tr>
                <td><?= h($mod['code']) ?></td>
                <td><?= h($mod['intitule']) ?></td>
                <td><?= h($mod['coefficient']) ?></td>
                <td><?= h($mod['niveau']) ?></td>
                <td><?= $nb_etuds ?></td>
                <td style="font-weight:600">
                    <?php if ($cls_avg !== null && $cls_avg > 0): ?>
                        <span class="<?= noteClass((float)$cls_avg) ?>"><?= number_format($cls_avg, 2) ?></span>
                    <?php else: ?>
                        <span style="color:var(--color-text-tertiary)">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>


    <!-- ════════════════════════════════════════════════════
         PANEL : EMPLOI DU TEMPS
    ════════════════════════════════════════════════════ -->
    <?php elseif ($panel === 'emploi'): ?>

    <div class="page-head">
        <div class="page-title">Timetable</div>
        <div class="page-sub"><?= h(APP_YEAR) ?></div>
    </div>

    <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
        <span style="display:inline-flex;align-items:center;gap:6px;font-size:11px">
            <span style="display:inline-block;width:12px;height:12px;background:#dbeafe;border-left:3px solid #1e40af"></span>
            Lecture — all students
        </span>
        <span style="display:inline-flex;align-items:center;gap:6px;font-size:11px">
            <span style="display:inline-block;width:12px;height:12px;background:var(--color-green-light);border-left:3px solid var(--color-green)"></span>
            TD — Directed Work (by group)
        </span>
        <span style="display:inline-flex;align-items:center;gap:6px;font-size:11px">
            <span style="display:inline-block;width:12px;height:12px;background:var(--color-amber-light);border-left:3px solid var(--color-amber)"></span>
            TP — Practical Work (by group)
        </span>
    </div>

    <?php
    $stmt = $pdo->prepare("SELECT * FROM emploi_du_temps WHERE enseignant_id = ? AND annee_univ = ? ORDER BY FIELD(jour,'Sunday','Monday','Tuesday','Wednesday','Thursday'), heure_debut");
    $stmt->execute([$user_id, APP_YEAR]);
    $seances = $stmt->fetchAll();

    $jours = ['Sunday','Monday','Tuesday','Wednesday','Thursday'];
    $slots = ['08:00','09:40','11:20','13:00','14:40'];
    $slot_labels = ['08:00–09:30','09:40–11:10','11:20–12:50','13:00–14:30','14:40–16:10'];

    // Index by [jour][heure_debut]
    $tt = [];
    foreach ($seances as $s) {
        $heure = substr($s['heure_debut'], 0, 5); // strip seconds → HH:MM
        $tt[$s['jour']][$heure] = $s;
    }
    ?>

    <div class="card" style="overflow-x:auto">
        <table class="tt-table">
            <thead>
                <tr>
                    <th style="width:110px">Time</th>
                    <?php foreach ($jours as $j): ?>
                    <th><?= $j ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($slots as $idx => $slot): ?>
            <tr>
                <td class="tt-time"><?= $slot_labels[$idx] ?></td>
                <?php foreach ($jours as $j):
                    $s = $tt[$j][$slot] ?? null;
                ?>
                <td style="padding:5px">
                    <?php if ($s): ?>
                        <div class="tt-<?= strtolower($s['type_seance']) ?>">
                            <div class="tt-type"><?= h($s['type_seance']) ?></div>
                            <?php if ($s['groupe'] !== 'All'): ?>
                            <div class="tt-group"><?= h($s['groupe']) ?></div>
                            <?php endif; ?>
                            <?php if ($s['salle']): ?>
                            <div class="tt-room"><?= h($s['salle']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <span style="color:var(--color-border);font-size:12px">—</span>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>


    <!-- ════════════════════════════════════════════════════
         PANEL : ENVOYER RÉSULTATS
    ════════════════════════════════════════════════════ -->
    <?php elseif ($panel === 'resultats'): ?>

    <div class="page-head">
        <div class="page-title">Send Results</div>
        <div class="page-sub">Summary table — send to department</div>
    </div>

    <?php
    // Collect all students across all modules with final average
    $all_results = [];
    foreach ($modules as $mod):
        $stmt = $pdo->prepare("
            SELECT e.matricule, e.nom, e.prenom, n.td, n.tp, n.exam, n.moyenne
            FROM inscriptions i
            JOIN etudiants e ON e.id = i.etudiant_id
            LEFT JOIN notes n ON n.etudiant_id = e.id AND n.module_id = ? AND n.annee_univ = ?
            WHERE i.module_id = ? AND i.annee_univ = ?
            ORDER BY e.nom, e.prenom
        ");
        $stmt->execute([$mod['id'], APP_YEAR, $mod['id'], APP_YEAR]);
        $rows = $stmt->fetchAll();
        if (!empty($rows)) $all_results[$mod['code'] . ' – ' . $mod['intitule']] = $rows;
    endforeach;

    // POST: send
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_results'])) {
        if ($module) {
            $count_q = $pdo->prepare("SELECT COUNT(*) FROM inscriptions WHERE module_id=? AND annee_univ=?");
            $count_q->execute([$module['id'], APP_YEAR]);
            $nb_etudiants = (int)$count_q->fetchColumn();

            $notes_q = $pdo->prepare("SELECT note FROM notes WHERE module_id=? AND annee_univ=? AND note IS NOT NULL");
            $notes_q->execute([$module['id'], APP_YEAR]);
            $notes = array_map('floatval', $notes_q->fetchAll(PDO::FETCH_COLUMN));
            $nb_notes = count($notes);
            $moyenne_classe = $nb_notes > 0 ? array_sum($notes) / $nb_notes : null;

            $save = $pdo->prepare("INSERT INTO resultats_envois (enseignant_id, module_id, annee_univ, nb_etudiants, nb_notes, moyenne_classe) VALUES (?, ?, ?, ?, ?, ?)");
            $save->execute([$user_id, $module['id'], APP_YEAR, $nb_etudiants, $nb_notes, $moyenne_classe]);
        }
        $notif = '<div class="notif notif-ok">&#10003; Results successfully sent to the department.</div>';
    }
    ?>

    <?= $notif ?>

    <div class="alert alert-info" style="margin-bottom:16px">
        &#9432;&nbsp; Check all grades before sending. You can send multiple times if changes are needed.
    </div>

    <?php foreach ($all_results as $mod_label => $rows): ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= h($mod_label) ?></div>
        </div>
        <table>
            <thead>
                <tr><th>#</th><th>Student ID</th><th>Full Name</th><th>Average finale /20</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
            <tr>
                <td style="color:var(--color-text-tertiary)"><?= $i + 1 ?></td>
                <td style="font-family:monospace;font-size:11px"><?= h($r['matricule']) ?></td>
                <td style="font-weight:600"><?= h($r['nom'] . ' ' . $r['prenom']) ?></td>
                <td style="font-weight:600">
                    <?php if ($r['moyenne'] !== null): ?>
                        <?= number_format($r['moyenne'], 2) ?>
                    <?php else: ?>
                        <span style="color:var(--color-text-tertiary)">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>

    <form method="POST" action="dashboard.php?panel=resultats" onsubmit="return confirm('Confirmer l\'envoi des résultats au département ?')">
        <button type="submit" name="send_results" class="btn-send">&#10148; Send to Department</button>
    </form>


    <!-- ════════════════════════════════════════════════════
         PANEL : ANNOUNCEMENTS
    ════════════════════════════════════════════════════ -->
    <?php elseif ($panel === 'posts'): ?>

    <div class="page-head">
        <div class="page-title">Announcements</div>
        <div class="page-sub">Post homework, exam dates, or any message to your students</div>
    </div>

    <?= $notif ?>

    <!-- New post form -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-header">
            <div class="card-title">New Post</div>
        </div>
        <form method="POST" action="dashboard.php?panel=posts">
            <div class="fg">
                <label>Module</label>
                <select name="module_id">
                    <?php foreach ($modules as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= h($m['code']) ?> — <?= h($m['intitule']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Message (text, link, anything)</label>
                <textarea name="contenu" rows="4" style="width:100%;padding:8px 10px;border:1px solid var(--color-border);border-radius:var(--radius-md);font-size:13px;font-family:inherit;resize:vertical;outline:none" placeholder="Write your announcement here…"></textarea>
            </div>
            <button type="submit" name="new_post" class="btn-blue" style="padding:8px 20px;font-size:13px">Publish</button>
        </form>
    </div>

    <!-- Existing posts -->
    <?php
    $stmt = $pdo->prepare("
        SELECT p.*, m.code, m.intitule
        FROM posts p
        JOIN modules m ON m.id = p.module_id
        WHERE p.enseignant_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll();

    if (empty($posts)):
    ?>
        <div class="card">
            <div style="font-size:12px;color:var(--color-text-secondary);text-align:center;padding:20px">
                No announcements yet. Write your first post above!
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post):
            // Load comments for this post
            $cstmt = $pdo->prepare("
                SELECT c.*, e.nom, e.prenom
                FROM commentaires c
                JOIN etudiants e ON e.id = c.etudiant_id
                WHERE c.post_id = ?
                ORDER BY c.created_at ASC
            ");
            $cstmt->execute([$post['id']]);
            $comments = $cstmt->fetchAll();
        ?>
        <div class="card" style="margin-bottom:14px">
            <!-- Post header -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px">
                <div style="display:flex;align-items:center;gap:10px">
                    <div class="avatar av-green"><?= h($initials) ?></div>
                    <div>
                        <div style="font-size:13px;font-weight:600"><?= h($ens['grade'] . ' ' . $ens['nom']) ?></div>
                        <div style="font-size:10px;color:var(--color-text-tertiary)">
                            <?= h($post['code']) ?> · <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <form method="POST" action="dashboard.php?panel=posts" onsubmit="return confirm('Delete this post?')">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <button type="submit" name="delete_post" class="btn-action btn-danger" style="font-size:10px">Delete</button>
                </form>
            </div>

            <!-- Post content -->
            <div style="font-size:13px;color:var(--color-text);line-height:1.7;white-space:pre-wrap;margin-bottom:14px;padding:10px;background:var(--color-bg-secondary);border-radius:var(--radius-sm)"><?= h($post['contenu']) ?></div>

            <!-- Comments -->
            <?php if (!empty($comments)): ?>
            <div style="border-top:1px solid var(--color-border-light);padding-top:10px">
                <div style="font-size:10px;font-weight:700;color:var(--color-text-tertiary);margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em">
                    <?= count($comments) ?> comment<?= count($comments) !== 1 ? 's' : '' ?>
                </div>
                <?php foreach ($comments as $c): ?>
                <div style="display:flex;gap:8px;margin-bottom:8px;align-items:flex-start">
                    <div class="avatar av-blue" style="width:24px;height:24px;font-size:8px;flex-shrink:0">
                        <?= strtoupper(substr($c['prenom'],0,1) . substr($c['nom'],0,1)) ?>
                    </div>
                    <div style="flex:1;background:var(--color-bg-secondary);border-radius:var(--radius-sm);padding:6px 10px">
                        <div style="font-size:11px;font-weight:600;margin-bottom:2px"><?= h($c['prenom'] . ' ' . $c['nom']) ?></div>
                        <div style="font-size:12px;color:var(--color-text)"><?= h($c['contenu']) ?></div>
                        <div style="font-size:10px;color:var(--color-text-tertiary);margin-top:3px"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="font-size:11px;color:var(--color-text-tertiary);border-top:1px solid var(--color-border-light);padding-top:8px">
                No comments yet.
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>


    <!-- ════════════════════════════════════════════════════
         PANEL : RANKINGS
    ════════════════════════════════════════════════════ -->
    <?php elseif ($panel === 'ranking'): ?>

    <div class="page-head">
        <div class="page-title">Student Rankings</div>
        <div class="page-sub">Students ranked by average — 1st, 2nd, 3rd…</div>
    </div>

    <?php foreach ($modules as $mod):
        $stmt = $pdo->prepare("
            SELECT e.nom, e.prenom, e.matricule, n.moyenne,
                   RANK() OVER (ORDER BY n.moyenne DESC) as classement
            FROM notes n
            JOIN etudiants e ON e.id = n.etudiant_id
            WHERE n.module_id = ? AND n.annee_univ = ?
            AND n.moyenne IS NOT NULL
            ORDER BY n.moyenne DESC
        ");
        $stmt->execute([$mod['id'], APP_YEAR]);
        $rankings = $stmt->fetchAll();
    ?>
    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div class="card-title"><?= h($mod['code']) ?> — <?= h($mod['intitule']) ?></div>
            <span class="badge b-blue"><?= count($rankings) ?> students</span>
        </div>
        <?php if (empty($rankings)): ?>
            <div style="font-size:12px;color:var(--color-text-secondary)">No grades entered yet.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr><th>Rank</th><th>Student ID</th><th>Full Name</th><th>Average /20</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rankings as $r):
                $rank = (int)$r['classement'];
                // Medal for top 3
                $medal = match($rank) {
                    1 => '🥇',
                    2 => '🥈',
                    3 => '🥉',
                    default => '#' . $rank
                };
            ?>
            <tr>
                <td style="font-weight:700;font-size:<?= $rank <= 3 ? '16px' : '12px' ?>;text-align:center"><?= $medal ?></td>
                <td style="font-family:monospace;font-size:11px"><?= h($r['matricule']) ?></td>
                <td style="font-weight:<?= $rank <= 3 ? '700' : '400' ?>"><?= h($r['nom'] . ' ' . $r['prenom']) ?></td>
                <td style="font-weight:700"><span class="<?= noteClass($r['moyenne']) ?>"><?= number_format($r['moyenne'], 2) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- ════════════════════════════════════════════════════
         PANEL : PROFIL
    ════════════════════════════════════════════════════ -->
    <?php elseif ($panel === 'profil'): ?>

    <div class="page-head">
        <div class="page-title">My Profile</div>
        <div class="page-sub">Personal Information</div>
    </div>

    <div class="card" style="max-width:520px">
        <div class="card-header">
            <div style="display:flex;align-items:center;gap:14px">
                <div class="avatar av-green" style="width:48px;height:48px;font-size:16px;font-weight:700"><?= h($initials) ?></div>
                <div>
                    <div style="font-size:16px;font-weight:600"><?= h($ens['grade'] . ' ' . $ens['prenom'] . ' ' . $ens['nom']) ?></div>
                    <div style="font-size:12px;color:var(--color-text-secondary)"><?= h($ens['departement']) ?></div>
                </div>
            </div>
        </div>
        <div class="info-row"><span class="info-key">Email</span><span class="info-val"><?= h($ens['email']) ?></span></div>
        <div class="info-row"><span class="info-key">Grade</span><span class="info-val"><?= h($ens['grade']) ?></span></div>
        <div class="info-row"><span class="info-key">Department</span><span class="info-val"><?= h($ens['departement']) ?></span></div>
        <?php if (!empty($ens['specialite'])): ?>
        <div class="info-row"><span class="info-key">Speciality</span><span class="info-val"><?= h($ens['specialite']) ?></span></div>
        <?php endif; ?>
        <div class="info-row"><span class="info-key">Assigned Modules</span><span class="info-val"><?= count($modules) ?></span></div>
        <div class="info-row"><span class="info-key">Academic Year</span><span class="info-val"><?= h(APP_YEAR) ?></span></div>
    </div>

    <?php endif; ?>

    </div><!-- /main-content -->
</div><!-- /dash-layout -->

<script>
// Live search for teacher dashboard tables
document.querySelectorAll('.search-notes').forEach(function(input) {
    input.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        var tableId = this.getAttribute('data-table');
        var table = document.getElementById(tableId);
        if (!table) return;
        table.querySelectorAll('tbody tr').forEach(function(row) {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
});
</script>
</body>
</html>
