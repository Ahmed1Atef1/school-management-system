<?php
require_once '../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

require_role('student');
$userId = current_user_id();

// Fetch overall grades summary
$stmtStats = $conn->prepare("
    SELECT 
        COUNT(g.id) AS graded_assignments,
        AVG(g.score) AS average_score
    FROM grades g
    WHERE g.user_id = ?
");
$stmtStats->bind_param('i', $userId);
$stmtStats->execute();
$stats = $stmtStats->get_result()->fetch_assoc();
$stmtStats->close();

$avgScore = $stats['average_score'] !== null ? round($stats['average_score'], 1) : 0;
$gradedCount = (int) $stats['graded_assignments'];

// Define simple GPA scale based on percentage (Simplified)
$gpa = 0.0;
$letterGrade = 'N/A';
if ($avgScore >= 90) { $gpa = 4.0; $letterGrade = 'A'; }
elseif ($avgScore >= 80) { $gpa = 3.0; $letterGrade = 'B'; }
elseif ($avgScore >= 70) { $gpa = 2.0; $letterGrade = 'C'; }
elseif ($avgScore >= 60) { $gpa = 1.0; $letterGrade = 'D'; }
elseif ($avgScore > 0)   { $gpa = 0.0; $letterGrade = 'F'; }

// Fetch per-course grades
$stmtCourses = $conn->prepare("
    SELECT 
        c.name, c.color,
        AVG(g.score) AS course_avg,
        COUNT(g.id) AS assignments_graded
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    LEFT JOIN assignments a ON a.course_id = c.id
    LEFT JOIN grades g ON g.assignment_id = a.id AND g.user_id = ?
    WHERE e.user_id = ?
    GROUP BY c.id
");
$stmtCourses->bind_param('ii', $userId, $userId);
$stmtCourses->execute();
$courseStats = $stmtCourses->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtCourses->close();

// Fetch recent grades history
$stmtHistory = $conn->prepare("
    SELECT 
        a.title, a.max_score, 
        c.name AS course_name, c.color AS course_color,
        g.score, g.graded_at
    FROM grades g
    JOIN assignments a ON a.id = g.assignment_id
    JOIN courses c ON c.id = a.course_id
    WHERE g.user_id = ?
    ORDER BY g.graded_at DESC
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
        <h1 class="dashboard-title">My Grades & Performance</h1>
        <p class="text-muted mb-0">Review your academic performance, GPA, and recent assignment scores.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- GPA Card -->
    <div class="col-md-4">
        <div class="dashboard-card h-100 border-0 shadow-sm text-center bg-primary text-white">
            <h6 class="text-white-50 text-uppercase mb-3">Overall GPA</h6>
            <h1 class="display-3 fw-bold mb-0"><?= number_format($gpa, 1); ?></h1>
            <p class="text-white-50 mt-2 mb-0">Letter Grade: <strong><?= $letterGrade; ?></strong></p>
        </div>
    </div>
    
    <!-- Average Score Card -->
    <div class="col-md-4">
        <div class="dashboard-card h-100 border-0 shadow-sm text-center">
            <h6 class="text-muted text-uppercase mb-3">Average Score</h6>
            <h1 class="display-3 fw-bold"><?= $avgScore; ?>%</h1>
            <p class="text-muted mt-2 mb-0">Across all courses</p>
        </div>
    </div>

    <!-- Assignments Graded Card -->
    <div class="col-md-4">
        <div class="dashboard-card h-100 border-0 shadow-sm text-center">
            <h6 class="text-muted text-uppercase mb-3">Completed Assignments</h6>
            <h1 class="display-3 fw-bold text-success"><?= $gradedCount; ?></h1>
            <p class="text-muted mt-2 mb-0">Total assignments graded</p>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Course Averages -->
    <div class="col-lg-5">
        <div class="dashboard-card h-100" style="padding: 0;">
            <div class="border-bottom p-4">
                <h5 class="mb-0 fw-bold" style="color: var(--app-text);">Performance by Course</h5>
            </div>
            <div class="p-4">
                <?php foreach ($courseStats as $cs): 
                    $cAvg = $cs['course_avg'] !== null ? round($cs['course_avg']) : 0;
                    $color = $cs['color'] ?: 'primary';
                    $graded = $cs['assignments_graded'];
                ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold"><?= htmlspecialchars($cs['name']); ?></span>
                            <span class="fw-bold text-<?= $color; ?>"><?= $cs['course_avg'] !== null ? $cAvg . '%' : 'N/A'; ?></span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-<?= $color; ?>" role="progressbar" style="width: <?= $cAvg; ?>%" aria-valuenow="<?= $cAvg; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted d-block mt-1"><?= $graded; ?> assignment(s) graded</small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Grades Table -->
    <div class="col-lg-7">
        <div class="dashboard-card h-100" style="padding: 0;">
            <div class="border-bottom p-4">
                <h5 class="mb-0 fw-bold" style="color: var(--app-text);">Recent Grades</h5>
            </div>
            <div class="p-0">
                <?php if (empty($history)): ?>
                    <div class="text-center py-5">
                        <p class="text-muted">No graded assignments found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-4">Assignment</th>
                                    <th>Course</th>
                                    <th class="text-center">Score</th>
                                    <th class="pe-4 text-end">Date Graded</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $h): 
                                    $cColor = $h['course_color'] ?: 'primary';
                                    $score = $h['score'];
                                    $max = $h['max_score'];
                                    $pct = $max > 0 ? round(($score / $max) * 100) : 0;
                                    
                                    $scoreColor = 'danger';
                                    if ($pct >= 85) $scoreColor = 'success';
                                    elseif ($pct >= 70) $scoreColor = 'warning';
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-bold">
                                            <?= htmlspecialchars($h['title']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $cColor; ?> bg-opacity-10 text-<?= $cColor; ?>">
                                                <?= htmlspecialchars($h['course_name']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="fw-bold text-<?= $scoreColor; ?> fs-6">
                                                <?= $score; ?> <span class="text-muted fw-normal" style="font-size: 0.75rem;">/ <?= $max; ?></span>
                                            </div>
                                        </td>
                                        <td class="pe-4 text-end text-muted small">
                                            <?= date('M d, Y', strtotime($h['graded_at'])); ?>
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
