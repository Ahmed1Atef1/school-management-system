<?php
require_once BASE_PATH . '/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_nav_active(string $pathKeyword): string
{
    $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';

    if ($pathKeyword === 'home') {
        return (strpos($currentScript, 'home.php') !== false) ? 'active' : '';
    }

    return (strpos($currentScript, $pathKeyword) !== false) ? 'active' : '';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LearnSphere</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.6/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('assets/css/custom.css'); ?>">

    <!-- Dark Mode Init (before paint to prevent FOUC) -->
    <script>
        (function () {
            var saved = localStorage.getItem('theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (saved === 'dark' || (!saved && prefersDark)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
            var sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                document.documentElement.setAttribute('data-sidebar', 'collapsed');
            }
        })();
    </script>
</head>
<body>

<!-- ========== APP SHELL ========== -->
<div class="app-shell">

    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>

    <!-- Main Area (topbar + content) -->
    <div class="app-main" id="appMain">

        <?php require_once BASE_PATH . '/includes/topbar.php'; ?>

        <!-- Page Content Wrapper -->
        <main class="app-content" id="appContent">
