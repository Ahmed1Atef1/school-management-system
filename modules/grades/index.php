<?php
require_once '../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

if (!user_has_role(['admin', 'teacher'])) redirect_to('home.php');

$success = $error = '';

// ── Handle grade save ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
    $scores = $_POST['score'] ?? [];  // [user_id => score]
    $assignmentId = (int) ($_POST['assignment_id'] ?? 0);

    $stmt = $conn->prepare("
        INSERT INTO grades (user_id, assignment_id, score)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE score = VALUES(score), graded_at = NOW()
    ");
    $stmtTitle = $conn->prepare("SELECT title, max_score FROM assignments WHERE id = ?");
    if ($stmtTitle) {
        $stmtTitle->bind_param('i', $assignmentId);
        $stmtTitle->execute();
        $assRes = $stmtTitle->get_result()->fetch_assoc();
        $stmtTitle->close();
    }
    $assTitle = $assRes['title'] ?? 'Assignment';
    $assMax   = $assRes['max_score'] ?? 100;

    require_once BASE_PATH . '/includes/notifications_helper.php';

    $saved = 0;
    foreach ($scores as $uid => $score) {
        $uid   = (int) $uid;
        if ($score === '') continue; // Skip empty inputs
        $score = max(0, (int) $score);
        $stmt->bind_param('iii', $uid, $assignmentId, $score);
        $stmt->execute();
        $saved++;
        
        send_notification(
            $conn, $uid, 'grade', 
            "Assignment Graded", 
            "Your assignment '{$assTitle}' was graded: {$score}/{$assMax}.", 
            'bi-graph-up-arrow', 'success', 
            app_url('modules/student/grades.php')
        );
    }
    $stmt->close();
    $success = "Grades saved for {$saved} students.";
}

// ── Filter by assignment ───────────────────────────────────────────
$selectedAssignment = (int) ($_GET['assignment_id'] ?? $_POST['assignment_id'] ?? 0);

// Fetch all assignments for dropdown
$assignments = $conn->query("
    SELECT a.id, a.title, a.max_score, c.name AS course
    FROM assignments a JOIN courses c ON c.id = a.course_id
    ORDER BY a.due_date DESC
")->fetch_all(MYSQLI_ASSOC);

// Students enrolled in this assignment's course + existing grades
$students    = [];
$assignInfo  = null;

if ($selectedAssignment) {
    // Get assignment info
    $stmtAss = $conn->prepare("
        SELECT a.id, a.title, a.max_score, a.course_id, c.name AS course
        FROM assignments a JOIN courses c ON c.id = a.course_id
        WHERE a.id = ?
    ");
    $stmtAss->bind_param('i', $selectedAssignment);
    $stmtAss->execute();
    $assignInfo = $stmtAss->get_result()->fetch_assoc();
    $stmtAss->close();

    if ($assignInfo) {
        // Students enrolled in that course
        $stmtS = $conn->prepare("
            SELECT u.id, u.username,
                   COALESCE(g.score, '') AS score
            FROM users u
            JOIN enrollments e ON e.user_id = u.id AND e.course_id = ?
            LEFT JOIN grades g ON g.user_id = u.id AND g.assignment_id = ?
            WHERE u.role = 'student'
            ORDER BY u.username ASC
        ");
        $stmtS->bind_param('ii', $assignInfo['course_id'], $selectedAssignment);
        $stmtS->execute();
        $students = $stmtS->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtS->close();
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Grades</h2>
        <p class="text-muted small mb-0">Record student scores per assignment.</p>
    </div>
    <a href="<?= app_url('modules/assignments/index.php'); ?>" class="btn btn-outline-secondary rounded-pill">
        <i class="bi bi-journal-text me-2"></i>Manage Assignments
    </a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success rounded-3"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Assignment Selector -->
<div class="card border-0 shadow-sm mb-4" style="background:var(--app-surface);border:1px solid var(--app-border)!important;border-radius:18px;">
    <div class="card-body p-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Select Assignment</label>
                <select name="assignment_id" class="form-select" onchange="this.form.submit()">
                    <option value="">— Choose assignment —</option>
                    <?php foreach ($assignments as $a): ?>
                        <option value="<?= $a['id']; ?>" <?= $selectedAssignment === (int)$a['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($a['course'] . ' — ' . $a['title']); ?> (max: <?= $a['max_score']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Grade Table -->
<?php if ($selectedAssignment && $assignInfo && !empty($students)): ?>
<form method="POST">
    <input type="hidden" name="assignment_id" value="<?= $selectedAssignment; ?>">
    <input type="hidden" name="save_grades" value="1">

    <div class="mb-3 d-flex align-items-center gap-3">
        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
            <i class="bi bi-journal-text me-1"></i>
            <?= htmlspecialchars($assignInfo['course'] . ' — ' . $assignInfo['title']); ?>
        </span>
        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2">
            Max score: <?= $assignInfo['max_score']; ?>
        </span>
    </div>

    <div class="table-wrapper table-responsive border-0 shadow-sm mb-4"
         style="border-radius:20px;overflow:hidden;background:var(--app-surface);border:1px solid var(--app-border)!important;">
        <table class="table table-hover mb-0 align-middle">
            <thead style="background:var(--app-surface-soft);">
                <tr>
                    <th class="ps-4 py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Student</th>
                    <th class="py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Score <span class="text-muted fw-normal">/ <?= $assignInfo['max_score']; ?></span></th>
                    <th class="pe-4 py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s):
                    $score  = $s['score'] !== '' ? (int)$s['score'] : null;
                    $pct    = $score !== null && $assignInfo['max_score'] > 0
                              ? (int) round(($score / $assignInfo['max_score']) * 100) : null;
                    $letter = $pct === null ? '—'
                            : ($pct >= 90 ? 'A' : ($pct >= 80 ? 'B' : ($pct >= 70 ? 'C' : ($pct >= 60 ? 'D' : 'F'))));
                    $color  = $pct === null ? 'secondary'
                            : ($pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger'));
                ?>
                <tr>
                    <td class="ps-4 fw-semibold" style="border-bottom:1px solid var(--app-border);">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold"
                                 style="width:32px;height:32px;font-size:.85rem;">
                                <?= strtoupper(substr($s['username'], 0, 1)); ?>
                            </div>
                            <?= htmlspecialchars($s['username']); ?>
                        </div>
                    </td>
                    <td style="border-bottom:1px solid var(--app-border);">
                        <input type="number" name="score[<?= $s['id']; ?>]"
                               class="form-control form-control-sm" style="width:100px;"
                               min="0" max="<?= $assignInfo['max_score']; ?>"
                               value="<?= $score ?? ''; ?>"
                               placeholder="0–<?= $assignInfo['max_score']; ?>">
                    </td>
                    <td class="pe-4" style="border-bottom:1px solid var(--app-border);">
                        <span class="badge bg-<?= $color; ?>-subtle text-<?= $color; ?> rounded-pill px-3">
                            <?= $letter; ?> <?= $pct !== null ? "({$pct}%)" : ''; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <button type="submit" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-check2-all me-2"></i>Save Grades
    </button>
</form>

<?php elseif ($selectedAssignment && empty($students)): ?>
    <div class="card border-0 shadow-sm p-5 text-center" style="background:var(--app-surface);border-radius:20px;">
        <i class="bi bi-people text-muted" style="font-size:3rem;opacity:.5;"></i>
        <h5 class="mt-3">No enrolled students found for this assignment's course.</h5>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm p-5 text-center" style="background:var(--app-surface);border-radius:20px;">
        <i class="bi bi-graph-up-arrow text-muted" style="font-size:3rem;opacity:.5;"></i>
        <h5 class="mt-3">Select an assignment above to start grading.</h5>
    </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
