<?php
// teacher_dashboard.php
$userId = current_user_id();

// 1. Get Teacher ID
$stmtTeacher = $conn->prepare("SELECT t.id FROM teachers t JOIN users u ON u.email = t.email WHERE u.id = ?");
$stmtTeacher->bind_param("i", $userId);
$stmtTeacher->execute();
$teacherRes = $stmtTeacher->get_result()->fetch_assoc();
$teacherId = $teacherRes ? $teacherRes['id'] : 0;
$stmtTeacher->close();

// 2. Fetch Active Courses & Enrolled Students
$stmtStats1 = $conn->prepare("
    SELECT 
        COUNT(DISTINCT c.id) as active_courses,
        COUNT(DISTINCT e.user_id) as total_students,
        AVG(g.score) as avg_class_performance
    FROM courses c
    LEFT JOIN enrollments e ON e.course_id = c.id
    LEFT JOIN assignments a ON a.course_id = c.id
    LEFT JOIN grades g ON g.assignment_id = a.id
    WHERE c.teacher_id = ?
");
$stmtStats1->bind_param("i", $teacherId);
$stmtStats1->execute();
$stats1 = $stmtStats1->get_result()->fetch_assoc();
$stmtStats1->close();

$activeCourses = (int)($stats1['active_courses'] ?? 0);
$totalStudents = (int)($stats1['total_students'] ?? 0);
$avgPerformance = $stats1['avg_class_performance'] !== null ? round($stats1['avg_class_performance'], 1) : 0;

// 3. Fetch Assignments Created & Grades Recorded
$stmtStats2 = $conn->prepare("
    SELECT 
        COUNT(DISTINCT a.id) as assignments_created,
        COUNT(DISTINCT g.id) as grades_recorded
    FROM courses c
    LEFT JOIN assignments a ON a.course_id = c.id
    LEFT JOIN grades g ON g.assignment_id = a.id
    WHERE c.teacher_id = ?
");
$stmtStats2->bind_param("i", $teacherId);
$stmtStats2->execute();
$stats2 = $stmtStats2->get_result()->fetch_assoc();
$stmtStats2->close();

$assignmentsCreated = (int)($stats2['assignments_created'] ?? 0);
$gradesRecorded = (int)($stats2['grades_recorded'] ?? 0);

// 4. Fetch Attendance Sessions Count
$stmtStats3 = $conn->prepare("
    SELECT COUNT(DISTINCT a.date, a.course_id) as attendance_sessions
    FROM attendance a
    JOIN courses c ON a.course_id = c.id
    WHERE c.teacher_id = ?
");
$stmtStats3->bind_param("i", $teacherId);
$stmtStats3->execute();
$stats3 = $stmtStats3->get_result()->fetch_assoc();
$stmtStats3->close();

$attendanceSessions = (int)($stats3['attendance_sessions'] ?? 0);

// 5. Fetch Upcoming / Pending Tasks
$upcomingTasks = [];

// Get upcoming assignments (Due within 7 days)
$stmtUpcoming = $conn->prepare("
    SELECT a.title, a.due_date, c.name as course_name, c.color, 'assignment' as type
    FROM assignments a
    JOIN courses c ON c.id = a.course_id
    WHERE c.teacher_id = ? AND a.due_date >= CURDATE() AND a.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY a.due_date ASC
    LIMIT 4
");
$stmtUpcoming->bind_param("i", $teacherId);
$stmtUpcoming->execute();
$resUpcoming = $stmtUpcoming->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtUpcoming->close();

foreach ($resUpcoming as $task) {
    $upcomingTasks[] = [
        'title' => 'Due: ' . $task['title'],
        'course' => $task['course_name'],
        'color' => $task['color'],
        'date' => $task['due_date'],
        'icon' => 'bi-journal-check'
    ];
}

// 6. Teacher Overview Data for Chart
$stmtChart = $conn->prepare("
    SELECT c.name, AVG(g.score) as avg_score
    FROM courses c
    LEFT JOIN assignments a ON a.course_id = c.id
    LEFT JOIN grades g ON g.assignment_id = a.id
    WHERE c.teacher_id = ?
    GROUP BY c.id
");
$stmtChart->bind_param("i", $teacherId);
$stmtChart->execute();
$chartData = $stmtChart->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtChart->close();

$chartLabels = [];
$chartScores = [];
foreach ($chartData as $cd) {
    $chartLabels[] = $cd['name'];
    $chartScores[] = $cd['avg_score'] !== null ? round($cd['avg_score'], 1) : 0;
}

// 7. Intelligent Student Insights
$atRiskStudents = [];
$topStudents = [];

$stmtInsights = $conn->prepare("
    SELECT 
        u.id, u.username, c.name as course_name, c.color,
        (SELECT AVG(g.score) FROM grades g JOIN assignments a ON a.id = g.assignment_id WHERE g.user_id = u.id AND a.course_id = c.id) as avg_score,
        (SELECT COUNT(*) FROM attendance att WHERE att.user_id = u.id AND att.course_id = c.id) as total_att,
        (SELECT COUNT(*) FROM attendance att WHERE att.user_id = u.id AND att.course_id = c.id AND att.status IN ('present', 'late', 'excused')) as present_att
    FROM enrollments e
    JOIN users u ON u.id = e.user_id
    JOIN courses c ON c.id = e.course_id
    WHERE c.teacher_id = ?
");
$stmtInsights->bind_param("i", $teacherId);
$stmtInsights->execute();
$insightsData = $stmtInsights->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtInsights->close();

foreach ($insightsData as $row) {
    $avgScore = $row['avg_score'] !== null ? round($row['avg_score'], 1) : null;
    $attPct = $row['total_att'] > 0 ? round(($row['present_att'] / $row['total_att']) * 100) : null;
    
    // Top performers
    if ($avgScore !== null && $avgScore >= 90) {
        $topStudents[] = [
            'username' => $row['username'],
            'course' => $row['course_name'],
            'score' => $avgScore,
            'color' => 'success'
        ];
    }
    
    // At-risk students
    $riskReasons = [];
    if ($avgScore !== null && $avgScore < 60) $riskReasons[] = "Low Grade ({$avgScore}%)";
    if ($attPct !== null && $attPct < 75) $riskReasons[] = "Low Attendance ({$attPct}%)";
    
    if (!empty($riskReasons)) {
        $atRiskStudents[] = [
            'username' => $row['username'],
            'course' => $row['course_name'],
            'reason' => implode(', ', $riskReasons),
            'color' => 'danger'
        ];
    }
}

// Sort arrays
usort($topStudents, fn($a, $b) => $b['score'] <=> $a['score']);
$topStudents = array_slice($topStudents, 0, 4);
$atRiskStudents = array_slice($atRiskStudents, 0, 4);

?>

<!-- Hero Section -->
<div class="dashboard-hero mb-4">
    <div class="dashboard-hero-text">
        <span class="role-badge role-badge-teacher mb-3">
            <i class="bi bi-person-video3"></i>
            Teacher
        </span>
        <h1 class="dashboard-title">Teacher Control Panel</h1>
        <p class="text-muted mb-0">
            Welcome back, <strong><?= $username; ?></strong>.
            Review your students, track attendance, and manage grades.
        </p>
    </div>

    <div class="dashboard-hero-actions">
        <a href="<?= app_url('modules/assignments/index.php'); ?>" class="btn btn-primary rounded-pill">
            <i class="bi bi-journal-plus me-2"></i>Create Assignment
        </a>
        <a href="<?= app_url('modules/attendance/index.php'); ?>" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-calendar-check-fill me-2"></i>Mark Attendance
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4 stat-grid">
    <div class="col-md-6 col-xl-2">
        <div class="stat-card stat-card-students">
            <span class="stat-icon"><i class="bi bi-people-fill"></i></span>
            <div><strong><?= $totalStudents; ?></strong><span>Students</span></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2">
        <div class="stat-card stat-card-classrooms">
            <span class="stat-icon"><i class="bi bi-door-open-fill"></i></span>
            <div><strong><?= $activeCourses; ?></strong><span>Active Courses</span></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2">
        <div class="stat-card stat-card-classrooms">
            <span class="stat-icon"><i class="bi bi-calendar-check-fill"></i></span>
            <div><strong><?= $attendanceSessions; ?></strong><span>Attendance Sessions</span></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2">
        <div class="stat-card stat-card-teachers">
            <span class="stat-icon"><i class="bi bi-journal-text"></i></span>
            <div><strong><?= $assignmentsCreated; ?></strong><span>Assignments Created</span></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2">
        <div class="stat-card stat-card-users">
            <span class="stat-icon"><i class="bi bi-graph-up-arrow"></i></span>
            <div><strong><?= $gradesRecorded; ?></strong><span>Grades Recorded</span></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2">
        <div class="stat-card stat-card-users">
            <span class="stat-icon" style="color: #10b981;"><i class="bi bi-award-fill"></i></span>
            <div><strong><?= $avgPerformance; ?>%</strong><span>Avg Performance</span></div>
        </div>
    </div>
</div>

<!-- Intelligent Insights -->
<div class="row g-4 mb-4">
    <!-- At Risk Alerts -->
    <div class="col-lg-6">
        <div class="dashboard-card h-100" style="padding: 0;">
            <div class="border-bottom p-4 d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-4 me-3"></i>
                <h6 class="fw-bold mb-0 text-danger">At-Risk Student Alerts</h6>
            </div>
            <div class="p-0">
                <?php if (empty($atRiskStudents)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-shield-check fs-2 mb-2 d-block text-success"></i>
                        No students are currently at risk.
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($atRiskStudents as $risk): ?>
                            <li class="list-group-item bg-transparent p-3" style="border-bottom: 1px solid var(--app-border);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($risk['username']); ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($risk['course']); ?></small>
                                    </div>
                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2">
                                        <?= $risk['reason']; ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="col-lg-6">
        <div class="dashboard-card h-100" style="padding: 0;">
            <div class="border-bottom p-4 d-flex align-items-center">
                <i class="bi bi-star-fill text-success fs-4 me-3"></i>
                <h6 class="fw-bold mb-0 text-success">Top Performing Students</h6>
            </div>
            <div class="p-0">
                <?php if (empty($topStudents)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-bar-chart fs-2 mb-2 d-block"></i>
                        Not enough data yet.
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topStudents as $top): ?>
                            <li class="list-group-item bg-transparent p-3" style="border-bottom: 1px solid var(--app-border);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($top['username']); ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($top['course']); ?></small>
                                    </div>
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fs-6">
                                        <?= $top['score']; ?>%
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Upcoming Panel -->
<div class="row g-4 mb-5">
    <div class="col-lg-8">
        <div class="dashboard-card h-100">
            <h5 class="fw-bold mb-4">Class Performance Overview</h5>
            <div style="height: 300px; position: relative;">
                <?php if (empty($chartLabels)): ?>
                    <div class="d-flex h-100 align-items-center justify-content-center text-muted">
                        No course data available.
                    </div>
                <?php else: ?>
                    <canvas id="overviewChart"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dashboard-card h-100 d-flex flex-column" style="padding: 0;">
            <div class="border-bottom p-4">
                <h5 class="fw-bold mb-0" style="color: var(--app-text);">Upcoming Deadlines</h5>
            </div>
            <div class="p-0 flex-grow-1">
                <?php if (empty($upcomingTasks)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-check-circle fs-1 mb-2 d-block"></i>
                        All caught up!
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($upcomingTasks as $task): ?>
                            <li class="list-group-item bg-transparent p-3" style="border-bottom: 1px solid var(--app-border);">
                                <div class="d-flex align-items-center">
                                    <div class="icon-box icon-box-<?= $task['color']; ?> me-3">
                                        <i class="bi <?= $task['icon']; ?>"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($task['title']); ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($task['course']); ?> • <?= date('M d', strtotime($task['date'])); ?></small>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Access -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h4 mb-1">Teacher Workflows</h2>
        <p class="text-muted mb-0">Your most-used tools.</p>
    </div>
</div>
<div class="row g-4 mb-5 module-grid">
    <?php
    $teacherModules = [
        ['href' => 'modules/admin/students/index.php', 'icon' => 'bi-people-fill', 'css' => 'students', 'label' => 'My Students', 'desc' => 'View student records & insights.', 'count' => $totalStudents],
        ['href' => 'modules/attendance/index.php', 'icon' => 'bi-calendar-check-fill', 'css' => 'classrooms', 'label' => 'Attendance', 'desc' => 'Track class attendance.', 'count' => null],
        ['href' => 'modules/grades/index.php', 'icon' => 'bi-graph-up-arrow', 'css' => 'teachers', 'label' => 'Grades', 'desc' => 'Record and review grades.', 'count' => null],
        ['href' => 'modules/assignments/index.php', 'icon' => 'bi-journal-text', 'css' => 'users', 'label' => 'Assignments', 'desc' => 'Create and manage tasks.', 'count' => null],
        ['href' => 'modules/teacher/messages.php', 'icon' => 'bi-chat-dots-fill', 'css' => 'primary', 'label' => 'Announcements', 'desc' => 'Message your students.', 'count' => null],
        ['href' => 'modules/admin/courses/enrollments.php', 'icon' => 'bi-award-fill', 'css' => 'success', 'label' => 'Gamification', 'desc' => 'Award XP and Badges.', 'count' => null]
    ];
    foreach ($teacherModules as $mod): ?>
    <div class="col-md-6 col-xl-4 col-xxl-2">
        <a class="card h-100 module-card text-decoration-none" href="<?= app_url($mod['href']); ?>">
            <div class="card-body">
                <div class="d-flex flex-column align-items-center text-center gap-2">
                    <span class="module-icon module-icon-<?= $mod['css']; ?> mb-2" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="bi <?= $mod['icon']; ?>"></i>
                    </span>
                    <h6 class="card-title mb-1 fw-bold"><?= $mod['label']; ?></h6>
                    <p class="card-text text-muted small mb-0"><?= $mod['desc']; ?></p>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php if (!empty($chartLabels)): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('overviewChart');
    if (!ctx) return;
    
    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Average Score (%)',
                data: <?= json_encode($chartScores); ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.8)',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, max: 100 }
            }
        }
    });
});
</script>
<?php endif; ?>

