<?php
/**
 * sidebar.php â€” Role-based left sidebar for the dashboard layout.
 *
 * Navigation is fully segmented by role:
 *   admin   â†’ Full platform management + administration
 *   teacher â†’ Classroom-focused tools (students, attendance, grades, etc.)
 *   student â†’ Learner-focused tools (courses, grades, achievements, etc.)
 *
 * Placeholder links use modules/placeholder.php?module=Name
 * until their real pages are implemented.
 *
 * Requires: session started, app_url(), is_nav_active(), role_*() helpers.
 */

require_once BASE_PATH . '/includes/notifications_helper.php';

$currentRole = $_SESSION['role'] ?? '';
$isLoggedIn  = isset($_SESSION['username']);

// Helper: build a placeholder URL for a coming-soon module
if (!function_exists('ph')) {
    function ph(string $module): string {
        return app_url('modules/placeholder.php?module=' . urlencode($module));
    }
}
?>
<aside class="app-sidebar" id="appSidebar" data-role="<?= htmlspecialchars($currentRole); ?>">

    <!-- â”€â”€ Brand â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="sidebar-brand">
        <a href="<?= app_url('home.php'); ?>" class="sidebar-brand-link" aria-label="LearnSphere Home">
            <span class="sidebar-brand-icon">
                <i class="bi bi-mortarboard-fill"></i>
            </span>
            <span class="sidebar-brand-name">LearnSphere</span>
        </a>
    </div>

    <!-- â”€â”€ Navigation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <nav class="sidebar-nav" id="sidebarNav" aria-label="Main navigation">

        <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
             SHARED: Dashboard (all roles)
             â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
        <div class="sidebar-section-label">Main</div>

        <a href="<?= app_url('home.php'); ?>"
           class="sidebar-link <?= is_nav_active('home'); ?>"
           aria-current="<?= is_nav_active('home') ? 'page' : 'false'; ?>">
            <span class="sidebar-link-icon"><i class="bi bi-grid-1x2-fill"></i></span>
            <span class="sidebar-link-text">Dashboard</span>
        </a>

        <?php if ($isLoggedIn): ?>

        <?php /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
                 ADMIN NAVIGATION
                 â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */ ?>
        <?php if ($currentRole === 'admin'): ?>

            <div class="sidebar-section-label">Management</div>

            <a href="<?= app_url('modules/admin/students/index.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/admin/students'); ?>"
               aria-current="<?= is_nav_active('modules/admin/students') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-people-fill"></i></span>
                <span class="sidebar-link-text">Students</span>
            </a>

            <a href="<?= app_url('modules/admin/teachers/index.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/admin/teachers'); ?>"
               aria-current="<?= is_nav_active('modules/admin/teachers') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-person-video3"></i></span>
                <span class="sidebar-link-text">Teachers</span>
            </a>

            <a href="<?= app_url('modules/admin/classrooms/index.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/admin/classrooms'); ?>"
               aria-current="<?= is_nav_active('modules/admin/classrooms') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-door-open-fill"></i></span>
                <span class="sidebar-link-text">Classrooms</span>
            </a>

            <a href="<?= app_url('modules/admin/courses/index.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/admin/courses'); ?>"
               aria-current="<?= is_nav_active('modules/admin/courses') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-journal-bookmark-fill"></i></span>
                <span class="sidebar-link-text">Courses</span>
            </a>

            <a href="<?= app_url('modules/teacher/messages.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/teacher/messages'); ?>"
               aria-current="<?= is_nav_active('modules/teacher/messages') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-megaphone-fill"></i></span>
                <span class="sidebar-link-text">Announcements</span>
            </a>

            <div class="sidebar-section-label">Administration</div>

            <a href="<?= app_url('modules/admin/users/index.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/admin/users'); ?>"
               aria-current="<?= is_nav_active('modules/admin/users') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-person-gear"></i></span>
                <span class="sidebar-link-text">Users</span>
            </a>
            
            <a href="<?= app_url('modules/admin/courses/enrollments.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/admin/courses/enrollments'); ?>"
               aria-current="<?= is_nav_active('modules/admin/courses/enrollments') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-star-fill text-warning"></i></span>
                <span class="sidebar-link-text">Student XP &amp; Badges</span>
            </a>

            <a href="<?= ph('Analytics'); ?>"
               class="sidebar-link sidebar-link-soon">
                <span class="sidebar-link-icon"><i class="bi bi-bar-chart-line-fill"></i></span>
                <span class="sidebar-link-text">Analytics</span>
                <span class="sidebar-soon-badge">Soon</span>
            </a>

        <?php endif; /* end admin */ ?>

        <?php /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
                 TEACHER NAVIGATION
                 â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */ ?>
        <?php if ($currentRole === 'teacher'): ?>

            <div class="sidebar-section-label">Classroom</div>

            <a href="<?= app_url('modules/admin/students/index.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/admin/students'); ?>"
               aria-current="<?= is_nav_active('modules/admin/students') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-people-fill"></i></span>
                <span class="sidebar-link-text">My Students</span>
            </a>

            <a href="<?= app_url('modules/attendance/index.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/attendance'); ?>"
               aria-current="<?= is_nav_active('modules/attendance') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-calendar-check-fill"></i></span>
                <span class="sidebar-link-text">Attendance</span>
            </a>

            <a href="<?= app_url('modules/assignments/index.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/assignments'); ?>"
               aria-current="<?= is_nav_active('modules/assignments') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-journal-text"></i></span>
                <span class="sidebar-link-text">Assignments</span>
            </a>

            <a href="<?= app_url('modules/grades/index.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/grades'); ?>"
               aria-current="<?= is_nav_active('modules/grades') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-graph-up-arrow"></i></span>
                <span class="sidebar-link-text">Grades</span>
            </a>

            <a href="<?= app_url('modules/teacher/messages.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/teacher/messages'); ?>"
               aria-current="<?= is_nav_active('modules/teacher/messages') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-megaphone-fill"></i></span>
                <span class="sidebar-link-text">Announcements</span>
            </a>

            <a href="<?= ph('Schedule'); ?>"
               class="sidebar-link sidebar-link-soon">
                <span class="sidebar-link-icon"><i class="bi bi-calendar3"></i></span>
                <span class="sidebar-link-text">Schedule</span>
                <span class="sidebar-soon-badge">Soon</span>
            </a>

        <?php endif; /* end teacher */ ?>

        <?php /* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
                 STUDENT NAVIGATION
                 â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */ ?>
        <?php if ($currentRole === 'student'): ?>

            <div class="sidebar-section-label">Learning</div>

            <a href="<?= app_url('modules/student/courses.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/student/courses'); ?>"
               aria-current="<?= is_nav_active('modules/student/courses') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-book-fill"></i></span>
                <span class="sidebar-link-text">My Courses</span>
            </a>

            <a href="<?= app_url('modules/student/attendance.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/student/attendance'); ?>"
               aria-current="<?= is_nav_active('modules/student/attendance') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-calendar-check-fill"></i></span>
                <span class="sidebar-link-text">Attendance</span>
            </a>

            <a href="<?= app_url('modules/student/grades.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/student/grades'); ?>"
               aria-current="<?= is_nav_active('modules/student/grades') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-graph-up-arrow"></i></span>
                <span class="sidebar-link-text">Grades</span>
            </a>

            <a href="<?= app_url('modules/student/assignments.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/student/assignments'); ?>"
               aria-current="<?= is_nav_active('modules/student/assignments') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-journal-text"></i></span>
                <span class="sidebar-link-text">Assignments</span>
            </a>

            <div class="sidebar-section-label">Achievements</div>

            <a href="<?= app_url('modules/student/achievements.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/student/achievements'); ?>"
               aria-current="<?= is_nav_active('modules/student/achievements') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-trophy-fill"></i></span>
                <span class="sidebar-link-text">Achievements</span>
            </a>
            
            <a href="<?= app_url('modules/student/messages.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/student/messages'); ?>"
               aria-current="<?= is_nav_active('modules/student/messages') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon"><i class="bi bi-envelope-fill"></i></span>
                <span class="sidebar-link-text">Messages</span>
            </a>

            <?php 
                $unreadCount = get_unread_notification_count($conn, current_user_id());
            ?>
            <a href="<?= app_url('modules/student/notifications.php'); ?>"
               class="sidebar-link <?= is_nav_active('modules/student/notifications'); ?>"
               aria-current="<?= is_nav_active('modules/student/notifications') ? 'page' : 'false'; ?>">
                <span class="sidebar-link-icon">
                    <i class="bi bi-bell-fill"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="width: 10px; height: 10px; margin-top: 10px; margin-left: -5px;">
                            <span class="visually-hidden">New alerts</span>
                        </span>
                    <?php endif; ?>
                </span>
                <span class="sidebar-link-text">Notifications</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto"><?= $unreadCount; ?></span>
                <?php endif; ?>
            </a>

        <?php endif; /* end student */ ?>

        <?php endif; /* end isLoggedIn */ ?>

    </nav>

    <!-- â”€â”€ User Footer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="sidebar-footer">
        <div class="sidebar-user-info">
            <div class="sidebar-user-avatar sidebar-avatar-<?= role_color($currentRole); ?>">
                <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="sidebar-user-details">
                <div class="sidebar-user-name">
                    <?= htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?>
                </div>
                <div class="sidebar-user-role">
                    <span class="role-badge role-badge-<?= role_color($currentRole); ?>">
                        <i class="bi <?= role_icon($currentRole); ?>"></i>
                        <?= role_label($currentRole); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php if ($isLoggedIn): ?>
            <a href="<?= app_url('modules/admin/users/logout.php'); ?>"
               class="sidebar-logout-btn"
               title="Logout"
               aria-label="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        <?php endif; ?>
    </div>

</aside>

<!-- Mobile overlay â€” closes sidebar when tapped outside -->
<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>


