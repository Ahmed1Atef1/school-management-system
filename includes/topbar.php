<?php
/**
 * topbar.php — Sticky top bar for the dashboard layout.
 * Requires: session started, app_url() available.
 */
?>
<header class="app-topbar" id="appTopbar">

    <!-- Left: sidebar toggle + current page breadcrumb -->
    <div class="topbar-left">
        <button class="sidebar-toggle-btn"
                id="sidebarToggle"
                aria-label="Toggle sidebar"
                aria-expanded="true"
                aria-controls="appSidebar">
            <i class="bi bi-list"></i>
        </button>

        <nav class="topbar-breadcrumb d-none d-md-flex" aria-label="Breadcrumb">
            <span class="topbar-breadcrumb-app">EduPanel</span>
            <span class="topbar-breadcrumb-sep"><i class="bi bi-chevron-right"></i></span>
            <span class="topbar-breadcrumb-page" id="topbarPageTitle">Dashboard</span>
        </nav>
    </div>

    <!-- Right: theme toggle + user dropdown -->
    <div class="topbar-right">

        <!-- Theme Toggle -->
        <button id="themeToggle"
                class="topbar-icon-btn theme-toggle-btn"
                aria-label="Toggle theme">
            <i class="bi bi-moon-stars-fill dark-icon d-none"></i>
            <i class="bi bi-sun-fill light-icon"></i>
        </button>

        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="topbar-user-btn dropdown-toggle"
                    id="topbarUserDropdown"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-haspopup="true">
                <span class="topbar-avatar" aria-hidden="true">
                    <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                </span>
                <span class="topbar-username">
                    <?= htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                </span>
                <i class="bi bi-chevron-down topbar-chevron" aria-hidden="true"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end topbar-dropdown"
                aria-labelledby="topbarUserDropdown">
                <!-- User info header -->
                <li class="px-3 py-2">
                    <div class="fw-semibold" style="color: var(--app-text); font-size: 0.875rem;">
                        <?= htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                    </div>
                    <div class="mt-1">
                        <span class="role-badge role-badge-<?= role_color($_SESSION['role'] ?? ''); ?>" style="font-size: 0.7rem; padding: 2px 8px;">
                            <i class="bi <?= role_icon($_SESSION['role'] ?? ''); ?>"></i>
                            <?= role_label($_SESSION['role'] ?? ''); ?>
                        </span>
                    </div>
                </li>

                <li><hr class="dropdown-divider my-1" style="border-color: var(--app-border);"></li>

                <?php if (isset($_SESSION['username'])): ?>
                    <li>
                        <a class="dropdown-item" href="<?= app_url('modules/admin/users/logout.php'); ?>">
                            <i class="bi bi-box-arrow-right me-2 text-danger"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a class="dropdown-item" href="<?= app_url('modules/admin/users/login.php'); ?>">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            <span>Login</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

</header>

