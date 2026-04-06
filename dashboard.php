<?php
// ============================================================
//  dashboard.php — Routeur de tableau de bord
// ============================================================
require_once 'includes/auth.php';
require_login();

$role = get_role();

switch ($role) {
    case 'etudiant':
        require 'pages/dashboard_etudiant.php';
        break;
    case 'enseignant':
        require 'pages/dashboard_enseignant.php';
        break;
    case 'admin':
        require 'pages/dashboard_admin.php';
        break;
    default:
        logout();
}
