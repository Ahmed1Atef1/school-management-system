<?php
require_once '../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

require_role('student');
$userId = current_user_id();
$filterCourseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

// Fetch enrolled courses for the filter dropdown
$stmtC = $conn->prepare("
    SELECT c.id, c.name 
    FROM enrollments e 
    JOIN courses c ON c.id = e.course_id 
    WHERE e.user_id = ?
");
$stmtC->bind_param('i', $userId);
$stmtC->execute();
$enrolledCourses = $stmtC->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtC->close();

// Fetch assignments
$query = "
    SELECT 
        a.id, a.title, a.description, a.due_date, a.max_score,
        c.name AS course_name, c.color AS course_color,
        g.score, g.graded_at,
        DATEDIFF(a.due_date, CURDATE()) AS days_left
    FROM assignments a
    JOIN courses c ON c.id = a.course_id
    JOIN enrollments e ON e.course_id = c.id AND e.user_id = ?
    LEFT JOIN grades g ON g.assignment_id = a.id AND g.user_id = ?
";
if ($filterCourseId > 0) {
    $query .= " WHERE a.course_id = ?";
}
$query .= " ORDER BY a.due_date ASC";

$stmt = $conn->prepare($query);
if ($filterCourseId > 0) {
    $stmt->bind_param('iii', $userId, $userId, $filterCourseId);
} else {
    $stmt->bind_param('ii', $userId, $userId);
}
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-hero mb-4">
    <div class="dashboard-hero-text">
        <h1 class="dashboard-title">Assignments</h1>
        <p class="text-muted mb-0">Track your upcoming deadlines and review your graded work.</p>
    </div>
</div>

<div class="dashboard-card mb-4" style="padding: 0; overflow: hidden;">
    <div class="d-flex justify-content-between align-items-center border-bottom p-4 bg-transparent">
        <h5 class="mb-0 fw-bold">All Assignments</h5>
        <form method="GET" class="d-flex" style="max-width: 250px;">
            <select name="course_id" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                <option value="0">All Courses</option>
                <?php foreach ($enrolledCourses as $ec): ?>
                    <option value="<?= $ec['id']; ?>" <?= $filterCourseId === $ec['id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($ec['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="p-0">
        <?php if (empty($assignments)): ?>
            <div class="text-center py-5">
                <i class="bi bi-journal-check text-muted mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-muted">No assignments found</h5>
                <p class="text-muted">You're all caught up!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Assignment</th>
                            <th>Course</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): 
                            $isGraded = $a['score'] !== null;
                            $daysLeft = (int) $a['days_left'];
                            
                            $statusLabel = '';
                            $statusClass = '';
                            if ($isGraded) {
                                $statusLabel = 'Graded';
                                $statusClass = 'bg-success text-white';
                            } elseif ($daysLeft < 0) {
                                $statusLabel = 'Overdue';
                                $statusClass = 'bg-danger text-white';
                            } elseif ($daysLeft === 0) {
                                $statusLabel = 'Due Today';
                                $statusClass = 'bg-warning text-dark';
                            } else {
                                $statusLabel = 'Pending';
                                $statusClass = 'bg-secondary text-white';
                            }
                            
                            $cColor = $a['course_color'] ?: 'primary';
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?= htmlspecialchars($a['title']); ?></div>
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;">
                                        <?= htmlspecialchars($a['description']); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $cColor; ?> bg-opacity-10 text-<?= $cColor; ?>">
                                        <?= htmlspecialchars($a['course_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-nowrap">
                                        <i class="bi bi-calendar3 text-muted me-1"></i>
                                        <?= date('M d, Y', strtotime($a['due_date'])); ?>
                                    </div>
                                    <?php if (!$isGraded && $daysLeft > 0): ?>
                                        <small class="text-muted"><?= $daysLeft; ?> days left</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?= $statusClass; ?>">
                                        <?= $statusLabel; ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <?php if ($isGraded): ?>
                                        <div class="fw-bold fs-5">
                                            <?= $a['score']; ?> <span class="text-muted fs-6 fw-normal">/ <?= $a['max_score']; ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
