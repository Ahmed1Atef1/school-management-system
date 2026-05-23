<?php
require_once '../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

if (!user_has_role(['admin', 'teacher'])) redirect_to('home.php');

$success = $error = '';

// ── Courses dropdown ───────────────────────────────────────────────
$courses = $conn->query("SELECT id, name FROM courses ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// ── Handle new assignment creation ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assignment'])) {
    $courseId   = (int) ($_POST['course_id'] ?? 0);
    $title      = trim($_POST['title'] ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $dueDate    = $_POST['due_date'] ?? '';
    $maxScore   = max(1, (int) ($_POST['max_score'] ?? 100));

    if (!$courseId || !$title || !$dueDate) {
        $error = 'Course, title and due date are required.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO assignments (course_id, title, description, due_date, max_score)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('isssi', $courseId, $title, $desc, $dueDate, $maxScore);
        if ($stmt->execute()) {
            $success = "Assignment \"{$title}\" created successfully.";
            
            // Notify enrolled students
            require_once BASE_PATH . '/includes/notifications_helper.php';
            $stmtNotif = $conn->prepare("SELECT user_id FROM enrollments WHERE course_id = ?");
            if ($stmtNotif) {
                $stmtNotif->bind_param('i', $courseId);
                $stmtNotif->execute();
                $enrolled = $stmtNotif->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmtNotif->close();
                
                $msgDate = date('M d, Y', strtotime($dueDate));
                foreach ($enrolled as $en) {
                    send_notification(
                        $conn, $en['user_id'], 'assignment', 
                        "New Assignment: {$title}", 
                        "Due on {$msgDate}. Max score: {$maxScore}.", 
                        'bi-journal-text', 'primary', 
                        app_url("modules/student/assignments.php?course_id={$courseId}")
                    );
                }
            }
            
        } else {
            $error = 'Failed to create assignment. Please try again.';
        }
        $stmt->close();
    }
}

// ── Handle delete ─────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $delId = (int) $_GET['delete'];
    $conn->prepare("DELETE FROM assignments WHERE id = ?")->bind_param('i', $delId) || null;
    $stmtDel = $conn->prepare("DELETE FROM assignments WHERE id = ?");
    $stmtDel->bind_param('i', $delId);
    $stmtDel->execute();
    $stmtDel->close();
    $success = 'Assignment deleted.';
}

// ── Fetch existing assignments ─────────────────────────────────────
$filterCourse = (int) ($_GET['course_id'] ?? 0);
if ($filterCourse) {
    $stmtList = $conn->prepare("
        SELECT a.id, a.title, a.due_date, a.max_score, c.name AS course
        FROM assignments a JOIN courses c ON c.id = a.course_id
        WHERE a.course_id = ?
        ORDER BY a.due_date ASC
    ");
    $stmtList->bind_param('i', $filterCourse);
    $stmtList->execute();
    $assignments = $stmtList->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtList->close();
} else {
    $assignments = $conn->query("
        SELECT a.id, a.title, a.due_date, a.max_score, c.name AS course
        FROM assignments a JOIN courses c ON c.id = a.course_id
        ORDER BY a.due_date ASC
    ")->fetch_all(MYSQLI_ASSOC);
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Assignments</h2>
        <p class="text-muted small mb-0">Create and manage assignments per course.</p>
    </div>
    <button class="btn btn-primary rounded-pill" data-bs-toggle="collapse" data-bs-target="#createForm">
        <i class="bi bi-plus-lg me-2"></i>New Assignment
    </button>
</div>

<?php if ($success): ?>
    <div class="alert alert-success rounded-3"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Create Form (collapsible) -->
<div class="collapse mb-4 <?= $error ? 'show' : ''; ?>" id="createForm">
    <div class="card border-0 shadow-sm" style="background:var(--app-surface);border:1px solid var(--app-border)!important;border-radius:18px;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3">New Assignment</h6>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Course *</label>
                        <select name="course_id" class="form-select" required>
                            <option value="">— Select course —</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Title *</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Chapter 8 Quiz" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold small">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Instructions or notes..."></textarea>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">Due Date *</label>
                        <input type="date" name="due_date" class="form-control"
                               min="<?= date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">Max Score</label>
                        <input type="number" name="max_score" class="form-control" value="100" min="1" max="1000">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="save_assignment" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-plus-lg me-2"></i>Create Assignment
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="mb-3">
    <form method="GET" class="d-flex gap-2 align-items-center">
        <select name="course_id" class="form-select form-select-sm" style="width:220px;" onchange="this.form.submit()">
            <option value="">All courses</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= $c['id']; ?>" <?= $filterCourse === (int)$c['id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($c['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($filterCourse): ?>
            <a href="?" class="btn btn-sm btn-outline-secondary rounded-pill">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Table -->
<?php if (!empty($assignments)): ?>
<div class="table-wrapper table-responsive border-0 shadow-sm" style="border-radius:20px;overflow:hidden;background:var(--app-surface);border:1px solid var(--app-border)!important;">
    <table class="table table-hover mb-0 align-middle">
        <thead style="background:var(--app-surface-soft);">
            <tr>
                <th class="ps-4 py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Title</th>
                <th class="py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Course</th>
                <th class="py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Due Date</th>
                <th class="py-3 text-center" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Max Score</th>
                <th class="pe-4 py-3 text-end" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assignments as $a):
                $daysLeft  = (int) ceil((strtotime($a['due_date']) - time()) / 86400);
                $overdue   = $daysLeft < 0;
                $dueSoon   = !$overdue && $daysLeft <= 2;
            ?>
            <tr>
                <td class="ps-4 fw-semibold" style="border-bottom:1px solid var(--app-border);">
                    <?= htmlspecialchars($a['title']); ?>
                </td>
                <td style="border-bottom:1px solid var(--app-border);" class="text-muted">
                    <?= htmlspecialchars($a['course']); ?>
                </td>
                <td style="border-bottom:1px solid var(--app-border);">
                    <span class="badge rounded-pill <?= $overdue ? 'bg-danger' : ($dueSoon ? 'bg-warning text-dark' : 'bg-success-subtle text-success'); ?>">
                        <?= date('d M Y', strtotime($a['due_date'])); ?>
                        <?= $overdue ? ' (Overdue)' : ($dueSoon ? " (In {$daysLeft}d)" : ''); ?>
                    </span>
                </td>
                <td class="text-center text-muted" style="border-bottom:1px solid var(--app-border);">
                    <?= $a['max_score']; ?>
                </td>
                <td class="pe-4 text-end" style="border-bottom:1px solid var(--app-border);">
                    <a href="<?= app_url('modules/grades/index.php?assignment_id=' . $a['id']); ?>"
                       class="btn btn-sm btn-primary rounded-pill">
                        <i class="bi bi-graph-up-arrow me-1"></i>Grade
                    </a>
                    <a href="?delete=<?= $a['id']; ?>"
                       class="btn btn-sm btn-outline-danger rounded-pill ms-1"
                       onclick="return confirm('Delete this assignment?')">
                        <i class="bi bi-trash-fill"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <div class="card border-0 shadow-sm p-5 text-center" style="background:var(--app-surface);border-radius:20px;">
        <i class="bi bi-journal-text text-muted" style="font-size:3rem;opacity:.5;"></i>
        <h5 class="mt-3">No assignments yet.</h5>
        <p class="text-muted">Click "New Assignment" above to create one.</p>
    </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
