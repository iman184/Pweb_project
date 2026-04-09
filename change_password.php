<?php
// ============================================================
//  change_password.php — Changement de mot de passe obligatoire
// ============================================================
require_once 'includes/auth.php';

// Doit être connecté
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Si le mot de passe n'a pas besoin d'être changé → dashboard
if (empty($_SESSION['must_change_password'])) {
    header('Location: dashboard.php');
    exit;
}

// Seuls les étudiants et enseignants changent leur mot de passe (pas l'admin)
$role = get_role();
if ($role === 'admin') {
    $_SESSION['must_change_password'] = false;
    header('Location: dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pw  = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $result = change_password((int)$_SESSION['user_id'], $role, $new_pw, $confirm);

    if ($result['success']) {
        $success = 'Mot de passe changé avec succès ! Redirection...';
        header('refresh: 2; url=dashboard.php');
    } else {
        $error = $result['message'];
    }
}

$nom = get_user_name();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer le mot de passe – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .strength-bar { height: 5px; border-radius: 3px; background: var(--color-border); margin-top: 6px; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 3px; transition: width .3s, background .3s; width: 0%; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-logo">
        <div class="nav-logo-box"><span>FI</span></div>
        <div>
            <div class="nav-brand"><?= APP_NAME ?></div>
            <div class="nav-brand-sub"><?= APP_SUB ?></div>
        </div>
    </div>
    <div style="font-size:12px; color:var(--color-text-secondary);">
        Connecté en tant que <strong><?= h($nom) ?></strong>
    </div>
</nav>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-header">
            <div style="font-size:32px; margin-bottom:12px;">🔑</div>
            <div class="auth-title">Changez votre mot de passe</div>
            <div class="auth-sub">
                Bienvenue <strong><?= h($nom) ?></strong> !<br>
                Votre compte vient d'être créé par l'administrateur.<br>
                Vous devez choisir un nouveau mot de passe avant de continuer.
            </div>
        </div>

        <!-- Bannière d'avertissement -->
        <div style="background:#FEF3C7; border:1px solid #F59E0B; border-radius:var(--radius-md); padding:10px 14px; font-size:12px; color:#92400E; margin-bottom:18px; display:flex; gap:8px; align-items:flex-start;">
            <span style="font-size:16px;">⚠️</span>
            <div>
                <strong>Mot de passe temporaire détecté.</strong><br>
                Pour votre sécurité, vous ne pouvez pas utiliser le mot de passe fourni par l'administration.
            </div>
        </div>

        <?php if ($error):   echo alert('error',   $error);   endif; ?>
        <?php if ($success): echo alert('success', $success); endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="change_password.php">
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="new_password" id="new_pw"
                       placeholder="Minimum 6 caractères" required
                       oninput="checkStrength(this.value)">
                <div class="strength-bar">
                    <div class="strength-fill" id="strength-fill"></div>
                </div>
                <div id="strength-label" style="font-size:10px; color:var(--color-text-tertiary); margin-top:3px;"></div>
            </div>
            <div class="form-group">
                <label>Confirmer le nouveau mot de passe</label>
                <input type="password" name="confirm_password" placeholder="Répétez le mot de passe" required>
            </div>

            <!-- Règles -->
            <div style="background:var(--color-bg-secondary); border-radius:var(--radius-md); padding:10px 12px; font-size:11px; color:var(--color-text-secondary); margin-bottom:14px; line-height:1.9;">
                <strong style="display:block; margin-bottom:4px;">Règles :</strong>
                ✔ Minimum 6 caractères<br>
                ✔ Ne pas réutiliser le mot de passe temporaire<br>
                ✔ Retenez-le bien — il ne sera pas affiché de nouveau
            </div>

            <button type="submit" class="btn-full">Confirmer le nouveau mot de passe</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function checkStrength(pw) {
    var fill  = document.getElementById('strength-fill');
    var label = document.getElementById('strength-label');
    var score = 0;
    if (pw.length >= 6)  score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;

    var levels = [
        { pct: '0%',   color: '#E5E7EB', text: '' },
        { pct: '25%',  color: '#EF4444', text: 'Très faible' },
        { pct: '50%',  color: '#F97316', text: 'Faible' },
        { pct: '70%',  color: '#EAB308', text: 'Moyen' },
        { pct: '85%',  color: '#22C55E', text: 'Fort' },
        { pct: '100%', color: '#16A34A', text: 'Très fort' },
    ];
    var lvl = levels[score] || levels[0];
    fill.style.width      = lvl.pct;
    fill.style.background = lvl.color;
    label.textContent     = lvl.text;
    label.style.color     = lvl.color;
}
</script>
</body>
</html>
