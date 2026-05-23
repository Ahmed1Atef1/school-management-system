<?php
require_once 'config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/config/profile_sync.php';

$role     = current_user_role();
$username = htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8');

if ($role === 'admin') {
    sync_all_user_profiles($conn);

    $stats = ['students' => 0, 'teachers' => 0, 'classrooms' => 0, 'users' => 0, 'courses' => 0];
    foreach ([
        'students'   => 'students',
        'teachers'   => 'teachers',
        'classrooms' => 'class_rooms',
        'users'      => 'users',
        'courses'    => 'courses',
    ] as $key => $table) {
        $result      = $conn->query("SELECT COUNT(*) AS total FROM {$table}");
        $stats[$key] = (int) ($result->fetch_assoc()['total'] ?? 0);
    }
} elseif ($role === 'teacher') {
    sync_all_user_profiles($conn);
}

require_once BASE_PATH . '/includes/header.php';
?>

<?php /* ═══════════════════ STUDENT ════════════════════════════ */ ?>
<?php if ($role === 'student'): ?>
    <?php require BASE_PATH . '/modules/student/dashboard.php'; ?>

<?php /* ═══════════════════ TEACHER ════════════════════════════ */ ?>
<?php elseif ($role === 'teacher'): ?>
    <?php require BASE_PATH . '/modules/teacher/dashboard.php'; ?>

<?php /* ═══════════════════ ADMIN ══════════════════════════════ */ ?>
<?php elseif ($role === 'admin'): ?>

<!-- Hero Section -->
<div class="dashboard-hero mb-4">
    <div class="dashboard-hero-text">
        <span class="role-badge role-badge-<?= role_color($role); ?> mb-3">
            <i class="bi <?= role_icon($role); ?>"></i>
            <?= role_label($role); ?>
        </span>

        <h1 class="dashboard-title">Administrator Workspace</h1>
        <p class="text-muted mb-0">
            Welcome back, <strong><?= $username; ?></strong>.
            Manage students, teachers, classrooms and users from one central platform.
        </p>
    </div>

    <div class="dashboard-hero-actions">
        <a href="<?= app_url('modules/admin/students/create.php'); ?>" class="btn btn-primary rounded-pill">
            <i class="bi bi-person-plus-fill me-2"></i>Add Student
        </a>
        <a href="<?= app_url('modules/admin/teachers/create.php'); ?>" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-person-video3 me-2"></i>Add Teacher
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4 stat-grid">
    <div class="col-md-6 col-xl-3">
        <div class="stat-card stat-card-students">
            <span class="stat-icon"><i class="bi bi-people-fill"></i></span>
            <div><strong><?= $stats['students']; ?></strong><span>Total students</span></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card stat-card-teachers">
            <span class="stat-icon"><i class="bi bi-person-video3"></i></span>
            <div><strong><?= $stats['teachers']; ?></strong><span>Total teachers</span></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card stat-card-classrooms">
            <span class="stat-icon"><i class="bi bi-door-open-fill"></i></span>
            <div><strong><?= $stats['classrooms']; ?></strong><span>Total classrooms</span></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card stat-card-users">
            <span class="stat-icon"><i class="bi bi-person-gear"></i></span>
            <div><strong><?= $stats['users']; ?></strong><span>Total users</span></div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-4 mb-5">
    <div class="col-lg-8">
        <div class="dashboard-card h-100">
            <h5 class="fw-bold mb-4">School Overview</h5>
            <div style="height: 300px; position: relative;">
                <canvas id="overviewChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dashboard-card h-100 d-flex flex-column align-items-center">
            <h5 class="fw-bold mb-4 w-100 text-start">Distribution</h5>
            <div style="height: 250px; position: relative; width: 100%; display: flex; justify-content: center;">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>
    </div>
</div>
<script>window.appStats = <?= json_encode($stats); ?>;</script>

<!-- Module Grid -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h4 mb-1">Modules</h2>
        <p class="text-muted mb-0">Choose the area you want to manage.</p>
    </div>
</div>

<div class="row g-4 mb-5 module-grid">
    <?php
    $adminModules = [
        ['href' => 'modules/admin/students/index.php',  'img' => 'assets/images/students.jpg',  'icon' => 'bi-people-fill',    'css' => 'students',   'label' => 'Students',   'desc' => 'Records and contact details.',    'count' => $stats['students']],
        ['href' => 'modules/admin/teachers/index.php',  'img' => 'assets/images/teachers.jpg',  'icon' => 'bi-person-video3',  'css' => 'teachers',   'label' => 'Teachers',   'desc' => 'Subjects and contact details.',   'count' => $stats['teachers']],
        ['href' => 'modules/admin/classrooms/index.php','img' => 'assets/images/classes.jpg',   'icon' => 'bi-door-open-fill', 'css' => 'classrooms', 'label' => 'Classrooms', 'desc' => 'Rooms, locations, capacity.',     'count' => $stats['classrooms']],
        ['href' => 'modules/admin/courses/index.php',   'img' => 'assets/images/classes.jpg',   'icon' => 'bi-journal-bookmark-fill', 'css' => 'primary', 'label' => 'Courses', 'desc' => 'Subjects and teacher assignments.','count' => $stats['courses']],
        ['href' => 'modules/admin/users/index.php',     'img' => 'assets/images/users.jpg',     'icon' => 'bi-person-gear',    'css' => 'users',      'label' => 'Users',      'desc' => 'Accounts and access roles.',     'count' => $stats['users']],
    ];
    foreach ($adminModules as $mod): ?>
    <div class="col-md-6 col-xl-3">
        <a class="card h-100 module-card text-decoration-none" href="<?= app_url($mod['href']); ?>">
            <img src="<?= app_url($mod['img']); ?>" class="card-img-top module-img" alt="<?= $mod['label']; ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <span class="module-icon module-icon-<?= $mod['css']; ?>">
                            <i class="bi <?= $mod['icon']; ?>"></i>
                        </span>
                        <h5 class="card-title mb-1"><?= $mod['label']; ?></h5>
                        <p class="card-text text-muted mb-0"><?= $mod['desc']; ?></p>
                    </div>
                    <span class="module-count"><?= $mod['count']; ?></span>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; /* end roles */ ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

