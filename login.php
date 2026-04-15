<?php
// ============================================================
//  login.php — Page de connexion
// ============================================================
require_once 'includes/auth.php';

<<<<<<< HEAD
if (is_logged_in()) { header('Location: dashboard.php'); exit; }
=======
if (is_logged_in()) {
    header('Location: ' . get_dashboard_url());
    exit;
}
>>>>>>> d079fcc (Initial commit: Clean USTHB Portal Core)

$error    = '';
$pre_role = $_GET['role'] ?? 'etudiant';
if (!in_array($pre_role, ['etudiant', 'enseignant', 'admin'])) $pre_role = 'etudiant';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $password    = $_POST['password'] ?? '';
    $role        = $_POST['role'] ?? '';

    if (empty($identifiant) || empty($password) || empty($role)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $result = login($identifiant, $password, $role);
        if ($result['success']) {
            if (!empty($result['must_change'])) {
                header('Location: change_password.php');
<<<<<<< HEAD
            } else {
                header('Location: dashboard.php');
            }
=======
                exit;
            }

            header('Location: ' . get_dashboard_url());
>>>>>>> d079fcc (Initial commit: Clean USTHB Portal Core)
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion – <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
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
    <div class="nav-links">
        <a href="index.php" class="nav-link">Accueil</a>
        <a href="login.php" class="nav-link active">Connexion</a>
    </div>
</nav>

<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-header">
            <div style="font-size:28px; margin-bottom:10px;">🔐</div>
            <div class="auth-title">Connexion</div>
            <div class="auth-sub">Accédez à votre espace personnel USTHB Scolarité</div>
        </div>

        <?php if ($error): echo alert('error', $error); endif; ?>

        <form method="POST" action="login.php">
            <div class="rs-label">Se connecter en tant que</div>
            <div class="role-selector">
                <?php
                $roles = ['etudiant' => 'Étudiant', 'enseignant' => 'Enseignant', 'admin' => 'Admin'];
                foreach ($roles as $key => $label):
                    $sel = ($key === $pre_role) ? 'sel' : '';
                ?>
                <div class="rs-btn <?= $sel ?>" id="lr-<?= $key ?>" onclick="selRole('<?= $key ?>')">
                    <?= $label ?>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="role" id="role-input" value="<?= h($pre_role) ?>">

            <div class="form-group">
                <label id="identifiant-label">Email ou matricule</label>
                <input type="text" name="identifiant" id="identifiant-input"
                       placeholder="ex : 12345 ou email@usthb.dz"
                       value="<?= h($_POST['identifiant'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-full">Se connecter</button>
        </form>

        <div style="margin-top:20px; padding:12px; background:var(--color-bg-secondary); border-radius:var(--radius-md); font-size:11px; color:var(--color-text-secondary); line-height:1.8;">
            <strong style="display:block; margin-bottom:4px;">ℹ️ Information</strong>
            Les comptes sont créés uniquement par l'administrateur de la scolarité.<br>
            Contactez l'administration si vous n'avez pas encore vos identifiants.
        </div>

        <div style="margin-top:14px; padding-top:14px; border-top:1px solid var(--color-border-light);">
            <div style="font-size:10px;font-weight:700;color:var(--color-text-tertiary);margin-bottom:8px;text-transform:uppercase;letter-spacing:.06em;">Comptes de démonstration</div>
            <div style="font-size:11px;color:var(--color-text-secondary);line-height:2;">
                <strong>Étudiant :</strong> 12345 / password123<br>
                <strong>Enseignant :</strong> laachemi@usthb.dz / password123<br>
                <strong>Admin :</strong> admin@usthb.dz / password123
            </div>
        </div>
    </div>
</div>

<script>
function selRole(r) {
    document.querySelectorAll('.rs-btn').forEach(b => b.classList.remove('sel'));
    document.getElementById('lr-' + r).classList.add('sel');
    document.getElementById('role-input').value = r;
    var label = document.getElementById('identifiant-label');
    var input = document.getElementById('identifiant-input');
    if (r === 'etudiant') {
        label.textContent = 'Email ou matricule';
        input.placeholder = 'ex : 12345 ou email@usthb.dz';
    } else {
        label.textContent = 'Email';
        input.placeholder = 'email@usthb.dz';
    }
}
selRole('<?= h($pre_role) ?>');
</script>
</body>
</html>
