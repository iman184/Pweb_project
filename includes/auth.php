<?php
// ============================================================
//  auth.php — Fonctions d'authentification & session
// ============================================================

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ── Connexion ────────────────────────────────────────────── */
function login(string $identifiant, string $password, string $role): array {
    $pdo = get_pdo();

    $table = match($role) {
        'etudiant'   => 'etudiants',
        'enseignant' => 'enseignants',
        'admin'      => 'admins',
        default      => null,
    };

    if (!$table) {
        return ['success' => false, 'message' => 'Rôle invalide.'];
    }

    // Pour l'étudiant on accepte email OU matricule
    if ($role === 'etudiant') {
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE (email = ? OR matricule = ?) AND actif = 1");
        $stmt->execute([$identifiant, $identifiant]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE email = ? AND actif = 1");
        $stmt->execute([$identifiant]);
    }

    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['mot_de_passe'])) {
        return ['success' => false, 'message' => 'Identifiant ou mot de passe incorrect.'];
    }

    // Stocker dans la session
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_role'] = $role;
    $_SESSION['user_nom']  = $user['prenom'] . ' ' . $user['nom'];

    if ($role === 'etudiant') {
<<<<<<< HEAD
        $_SESSION['user_niveau']    = $user['niveau'] ?? '';
        $_SESSION['user_matricule'] = $user['matricule'] ?? '';
    }

    // Détecter si c'est la première connexion (mot de passe temporaire)
    $must_change = isset($user['must_change_password']) ? (bool)$user['must_change_password'] : false;
    $_SESSION['must_change_password'] = $must_change;

    return ['success' => true, 'must_change' => $must_change];
}

/* ── Créer un compte (réservé à l'admin) ─────────────────── */
function admin_create_account(array $data): array {
    $pdo  = get_pdo();
    $role = $data['role'] ?? '';

    if (empty($data['nom']) || empty($data['prenom']) || empty($data['email']) || empty($data['temp_password'])) {
=======
        $_SESSION['user_niveau'] = $user['niveau'] ?? '';
        $_SESSION['user_matricule'] = $user['matricule'] ?? '';
    }

    return ['success' => true];
}

/* ── Inscription ──────────────────────────────────────────── */
function register(array $data): array {
    $pdo = get_pdo();

    $role = $data['role'] ?? '';

    // Validation basique
    if (empty($data['nom']) || empty($data['prenom']) || empty($data['email']) || empty($data['password'])) {
>>>>>>> d079fcc (Initial commit: Clean USTHB Portal Core)
        return ['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires.'];
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Adresse email invalide.'];
    }
<<<<<<< HEAD
    if (strlen($data['temp_password']) < 4) {
        return ['success' => false, 'message' => 'Le mot de passe temporaire doit contenir au moins 4 caractères.'];
    }

    $hash = password_hash($data['temp_password'], PASSWORD_BCRYPT);

    try {
        if ($role === 'etudiant') {
            if (empty($data['matricule'])) {
                return ['success' => false, 'message' => 'Le matricule est obligatoire.'];
            }
            $chk = $pdo->prepare("SELECT id FROM etudiants WHERE matricule = ? OR email = ?");
            $chk->execute([$data['matricule'], $data['email']]);
            if ($chk->fetch()) {
                return ['success' => false, 'message' => 'Ce matricule ou cet email est déjà utilisé.'];
            }
            $stmt = $pdo->prepare("INSERT INTO etudiants (nom, prenom, email, matricule, niveau, date_naissance, mot_de_passe, must_change_password) VALUES (?,?,?,?,?,?,?,1)");
            $stmt->execute([
                $data['nom'], $data['prenom'], $data['email'],
                $data['matricule'], $data['niveau'] ?? 'L1 Info',
=======
    if (strlen($data['password']) < 6) {
        return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.'];
    }
    if ($data['password'] !== $data['confirm_password']) {
        return ['success' => false, 'message' => 'Les mots de passe ne correspondent pas.'];
    }

    $hash = password_hash($data['password'], PASSWORD_BCRYPT);

    try {
        if ($role === 'etudiant') {
            // Vérifier matricule unique
            $stmt = $pdo->prepare("SELECT id FROM etudiants WHERE matricule = ? OR email = ?");
            $stmt->execute([$data['matricule'], $data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Ce matricule ou cet email est déjà utilisé.'];
            }
            $stmt = $pdo->prepare("INSERT INTO etudiants (nom, prenom, email, matricule, niveau, date_naissance, mot_de_passe) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([
                $data['nom'], $data['prenom'], $data['email'],
                $data['matricule'], $data['niveau'],
>>>>>>> d079fcc (Initial commit: Clean USTHB Portal Core)
                $data['date_naissance'] ?? null, $hash
            ]);

        } elseif ($role === 'enseignant') {
<<<<<<< HEAD
            $chk = $pdo->prepare("SELECT id FROM enseignants WHERE email = ?");
            $chk->execute([$data['email']]);
            if ($chk->fetch()) {
                return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
            }
            $stmt = $pdo->prepare("INSERT INTO enseignants (nom, prenom, email, grade, departement, specialite, mot_de_passe, must_change_password) VALUES (?,?,?,?,?,?,?,1)");
            $stmt->execute([
                $data['nom'], $data['prenom'], $data['email'],
                $data['grade'] ?? 'Dr.', $data['departement'] ?? 'Informatique',
                $data['specialite'] ?? '', $hash
            ]);
        } else {
            return ['success' => false, 'message' => 'Rôle invalide (etudiant ou enseignant uniquement).'];
=======
            $stmt = $pdo->prepare("SELECT id FROM enseignants WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
            }
            $stmt = $pdo->prepare("INSERT INTO enseignants (nom, prenom, email, grade, departement, specialite, mot_de_passe) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([
                $data['nom'], $data['prenom'], $data['email'],
                $data['grade'], $data['departement'], $data['specialite'] ?? '', $hash
            ]);

        } elseif ($role === 'admin') {
            // Vérifier le code admin
            if (($data['code_admin'] ?? '') !== 'USTHB2025') {
                return ['success' => false, 'message' => 'Code d\'accès administrateur invalide.'];
            }
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
            }
            $stmt = $pdo->prepare("INSERT INTO admins (nom, prenom, email, service, mot_de_passe) VALUES (?,?,?,?,?)");
            $stmt->execute([
                $data['nom'], $data['prenom'], $data['email'],
                $data['service'] ?? '', $hash
            ]);
        } else {
            return ['success' => false, 'message' => 'Rôle invalide.'];
>>>>>>> d079fcc (Initial commit: Clean USTHB Portal Core)
        }
    } catch (\PDOException $e) {
        return ['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()];
    }

<<<<<<< HEAD
    return [
        'success'  => true,
        'message'  => 'Compte créé avec succès. Mot de passe temporaire : ' . $data['temp_password'],
        'temp_pw'  => $data['temp_password'],
    ];
}

/* ── Changer le mot de passe ──────────────────────────────── */
function change_password(int $user_id, string $role, string $new_password, string $confirm): array {
    if (strlen($new_password) < 6) {
        return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.'];
    }
    if ($new_password !== $confirm) {
        return ['success' => false, 'message' => 'Les mots de passe ne correspondent pas.'];
    }

    $table = match($role) {
        'etudiant'   => 'etudiants',
        'enseignant' => 'enseignants',
        default      => null,
    };

    if (!$table) return ['success' => false, 'message' => 'Rôle invalide.'];

    $hash = password_hash($new_password, PASSWORD_BCRYPT);
    $pdo  = get_pdo();
    $stmt = $pdo->prepare("UPDATE $table SET mot_de_passe = ?, must_change_password = 0 WHERE id = ?");
    $stmt->execute([$hash, $user_id]);

    $_SESSION['must_change_password'] = false;

    return ['success' => true];
=======
    return ['success' => true, 'message' => 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.'];
>>>>>>> d079fcc (Initial commit: Clean USTHB Portal Core)
}

/* ── Utilitaires ──────────────────────────────────────────── */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

function get_role(): string {
    return $_SESSION['user_role'] ?? '';
}

function get_user_name(): string {
    return $_SESSION['user_nom'] ?? '';
}

<<<<<<< HEAD
=======
function get_dashboard_url(): string {
    return match (get_role()) {
        'etudiant'   => 'student.php',
        'enseignant' => 'teacher.php',
        'admin'      => 'admin.php',
        default      => 'login.php',
    };
}

>>>>>>> d079fcc (Initial commit: Clean USTHB Portal Core)
function require_login(string $expected_role = ''): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
    if ($expected_role && get_role() !== $expected_role) {
<<<<<<< HEAD
        header('Location: dashboard.php');
=======
        header('Location: ' . get_dashboard_url());
>>>>>>> d079fcc (Initial commit: Clean USTHB Portal Core)
        exit;
    }
}

function logout(): void {
    session_destroy();
    header('Location: index.php');
    exit;
}

/* ── Helpers HTML ─────────────────────────────────────────── */
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function alert(string $type, string $msg): string {
    $class = $type === 'success' ? 'alert-success' : ($type === 'error' ? 'alert-error' : 'alert-info');
    return '<div class="alert ' . $class . '">' . h($msg) . '</div>';
<<<<<<< HEAD
}
=======
}
>>>>>>> d079fcc (Initial commit: Clean USTHB Portal Core)
