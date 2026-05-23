<?php
require_once '../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

require_role('student');
$userId = current_user_id();

// Fetch overall attendance stats
$stmtStats = $conn->prepare("
    SELECT 
        COUNT(*) AS total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_days,
        SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) AS excused_days
    FROM attendance
    WHERE user_id = ?
");
$stmtStats->bind_param('i', $userId);
$stmtStats->execute();
$stats = $stmtStats->get_result()->fetch_assoc();
$stmtStats->close();

$totalDays = (int) ($stats['total_days'] ?? 0);
$presentDays = (int) ($stats['present_days'] ?? 0);
$absentDays = (int) ($stats['absent_days'] ?? 0);
$lateDays = (int) ($stats['late_days'] ?? 0);
$excusedDays = (int) ($stats['excused_days'] ?? 0);

$goodDays = $presentDays + $lateDays + $excusedDays;
$overallPercent = $totalDays > 0 ? round(($goodDays / $totalDays) * 100) : 100;

// Fetch per-course breakdown
$stmtCourses = $conn->prepare("
    SELECT 
        c.name, c.color,
        COUNT(a.id) AS total_sessions,
        SUM(CASE WHEN a.status IN ('present', 'late', 'excused') THEN 1 ELSE 0 END) AS attended_sessions
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    LEFT JOIN attendance a ON a.course_id = c.id AND a.user_id = ?
    WHERE e.user_id = ?
    GROUP BY c.id
");
$stmtCourses->bind_param('ii', $userId, $userId);
$stmtCourses->execute();
$courseStats = $stmtCourses->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtCourses->close();

// Fetch recent attendance history
$stmtHistory = $conn->prepare("
    SELECT a.date, a.status, c.name AS course_name, c.color AS course_color
    FROM attendance a
    JOIN courses c ON c.id = a.course_id
    WHERE a.user_id = ?
    ORDER BY a.date DESC, a.recorded_at DESC
    LIMIT 30
");
$stmtHistory->bind_param('i', $userId);
$stmtHistory->execute();
$history = $stmtHistory->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtHistory->close();

require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-hero mb-4">
    <div class="dashboard-hero-text">
        <h1 class="dashboard-title">My Attendance</h1>
        <p class="text-muted mb-0">Track your attendance records and course participation.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="dashboard-card h-100 border-0 shadow-sm text-center">
            <h6 class="text-muted text-uppercase mb-3">Overall Rate</h6>
            <h1 class="display-4 fw-bold text-<?= $overallPercent >= 80 ? 'success' : ($overallPercent >= 60 ? 'warning' : 'danger'); ?>">
                <?= $overallPercent; ?>%
            </h1>
            <p class="text-muted mb-0">Total Attendance</p>
        </div>
    </div>
    <div class="col-md-6 col-xl-9">
        <div class="dashboard-card h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column justify-content-center">
                <div class="row text-center g-3">
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-success bg-opacity-10 rounded">
                            <h3 class="fw-bold text-success mb-1"><?= $presentDays; ?></h3>
                            <span class="text-success small">Present</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-warning bg-opacity-10 rounded">
                            <h3 class="fw-bold text-warning mb-1"><?= $lateDays; ?></h3>
                            <span class="text-warning small">Late</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-info bg-opacity-10 rounded">
                            <h3 class="fw-bold text-info mb-1"><?= $excusedDays; ?></h3>
                            <span class="text-info small">Excused</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-danger bg-opacity-10 rounded">
                            <h3 class="fw-bold text-danger mb-1"><?= $absentDays; ?></h3>
                            <span class="text-danger small">Absent</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Course Breakdown -->
    <div class="col-lg-5">
        <div class="dashboard-card h-100" style="padding: 0;">
            <div class="border-bottom p-4">
                <h5 class="mb-0 fw-bold" style="color: var(--app-text);">Per-Course Breakdown</h5>
            </div>
            <div class="p-4">
                <?php foreach ($courseStats as $cs): 
                    $total = (int) $cs['total_sessions'];
                    $attended = (int) $cs['attended_sessions'];
                    $pct = $total > 0 ? round(($attended / $total) * 100) : 100;
                    $color = $cs['color'] ?: 'primary';
                ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold"><?= htmlspecialchars($cs['name']); ?></span>
                            <span class="fw-bold text-<?= $color; ?>"><?= $pct; ?>%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-<?= $color; ?>" role="progressbar" style="width: <?= $pct; ?>%" aria-valuenow="<?= $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted d-block mt-1"><?= $attended; ?> of <?= $total; ?> sessions attended</small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent History Table -->
    <div class="col-lg-7">
        <div class="dashboard-card h-100" style="padding: 0;">
            <div class="border-bottom p-4">
                <h5 class="mb-0 fw-bold" style="color: var(--app-text);">Recent History</h5>
            </div>
            <div class="p-0">
                <?php if (empty($history)): ?>
                    <div class="text-center py-5">
                        <p class="text-muted">No attendance records found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Course</th>
                                    <th class="pe-4 text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $h): 
                                    $cColor = $h['course_color'] ?: 'primary';
                                    $status = $h['status'];
                                    
                                    $badgeClass = 'bg-secondary';
                                    if ($status === 'present') $badgeClass = 'bg-success';
                                    if ($status === 'absent') $badgeClass = 'bg-danger';
                                    if ($status === 'late') $badgeClass = 'bg-warning text-dark';
                                    if ($status === 'excused') $badgeClass = 'bg-info text-dark';
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <i class="bi bi-calendar-event text-muted me-2"></i>
                                            <?= date('D, M d, Y', strtotime($h['date'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $cColor; ?> bg-opacity-10 text-<?= $cColor; ?>">
                                                <?= htmlspecialchars($h['course_name']); ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <span class="badge <?= $badgeClass; ?> text-uppercase" style="font-size: 0.75rem;">
                                                <?= $status; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
