<?php
require_once '../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

require_role('student');
$userId = current_user_id();

// Fetch enrolled courses and aggregate data
$stmt = $conn->prepare("
    SELECT 
        c.id, c.name, c.description, c.icon, c.color,
        COALESCE(t.name, 'TBA') AS teacher,
        e.progress,
        (
            SELECT COUNT(*) FROM attendance a 
            WHERE a.course_id = c.id AND a.user_id = ? AND a.status IN ('present', 'late', 'excused')
        ) AS days_present,
        (
            SELECT COUNT(*) FROM attendance a 
            WHERE a.course_id = c.id AND a.user_id = ?
        ) AS total_days,
        (
            SELECT AVG(g.score) 
            FROM grades g
            JOIN assignments a2 ON a2.id = g.assignment_id
            WHERE a2.course_id = c.id AND g.user_id = ?
        ) AS avg_grade
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    LEFT JOIN teachers t ON t.id = c.teacher_id
    WHERE e.user_id = ?
    ORDER BY c.name ASC
");
$stmt->bind_param('iiii', $userId, $userId, $userId, $userId);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-hero mb-4">
    <div class="dashboard-hero-text">
        <h1 class="dashboard-title">My Courses</h1>
        <p class="text-muted mb-0">View your enrolled courses, track progress, and manage your learning.</p>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($courses)): ?>
        <div class="col-12">
            <div class="dashboard-card text-center py-5">
                <div class="">
                    <i class="bi bi-journal-x text-muted mb-3" style="font-size: 3rem;"></i>
                    <h4>No Courses Found</h4>
                    <p class="text-muted">You are not enrolled in any courses yet.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($courses as $c): 
            $attPercent = $c['total_days'] > 0 ? round(($c['days_present'] / $c['total_days']) * 100) : 100;
            $avgGrade   = $c['avg_grade'] !== null ? round($c['avg_grade'], 1) : 0;
            $color      = $c['color'] ?: 'primary';
            $icon       = $c['icon'] ?: 'bi-book-fill';
        ?>
            <div class="col-12 col-xl-6" id="course-<?= $c['id']; ?>">
                <div class="dashboard-card h-100" style="padding: 0;">
                    <div class="">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box icon-box-<?= $color; ?> me-3">
                                <i class="bi <?= $icon; ?>"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold"><?= htmlspecialchars($c['name']); ?></h5>
                                <small class="text-muted"><i class="bi bi-person-video3 me-1"></i> <?= htmlspecialchars($c['teacher']); ?></small>
                            </div>
                        </div>
                        
                        <p class="text-muted mb-4" style="min-height: 48px;">
                            <?= htmlspecialchars($c['description']); ?>
                        </p>

                        <div class="row g-3 mb-4 text-center">
                            <div class="col-4">
                                <div class="p-2 border rounded" style="background: var(--app-surface-soft);">
                                    <h5 class="mb-0 fw-bold text-<?= $color; ?>"><?= $c['progress']; ?>%</h5>
                                    <small class="text-muted" style="font-size: 0.75rem;">Progress</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 border rounded" style="background: var(--app-surface-soft);">
                                    <h5 class="mb-0 fw-bold text-success"><?= $attPercent; ?>%</h5>
                                    <small class="text-muted" style="font-size: 0.75rem;">Attendance</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 border rounded" style="background: var(--app-surface-soft);">
                                    <h5 class="mb-0 fw-bold text-info"><?= $c['avg_grade'] !== null ? $avgGrade . '%' : 'N/A'; ?></h5>
                                    <small class="text-muted" style="font-size: 0.75rem;">Avg Grade</small>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted fw-bold">Course Completion</small>
                                <small class="text-muted fw-bold"><?= $c['progress']; ?>%</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-<?= $color; ?>" role="progressbar" style="width: <?= $c['progress']; ?>%" aria-valuenow="<?= $c['progress']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top p-3 text-end">
                        <a href="<?= app_url('modules/student/assignments.php?course_id=' . $c['id']); ?>" class="btn btn-sm btn-outline-<?= $color; ?> rounded-pill px-3">
                            <i class="bi bi-journal-text me-1"></i> View Assignments
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
