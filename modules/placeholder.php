<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/includes/header.php';

$module = htmlspecialchars($_GET['module'] ?? 'This feature', ENT_QUOTES, 'UTF-8');
$role   = current_user_role();
?>

<div class="placeholder-page">
    <div class="placeholder-card dashboard-card text-center">

        <div class="placeholder-icon-wrap mb-4">
            <i class="bi bi-tools"></i>
        </div>

        <span class="role-badge role-badge-<?= role_color($role); ?> mb-3">
            <i class="bi <?= role_icon($role); ?>"></i>
            <?= role_label($role); ?> Feature
        </span>

        <h1 class="placeholder-title mt-3">Coming Soon</h1>
        <p class="placeholder-subtitle">
            <strong><?= $module; ?></strong> is part of the upcoming
            <?= role_label($role); ?> experience and will be available in a future update.
        </p>

        <div class="placeholder-actions">
            <a href="<?= app_url('home.php'); ?>" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>

    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
