<?php
require_once '../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

// Both admin and teacher can mark attendance
if (!user_has_role(['admin', 'teacher'])) {
    redirect_to('home.php');
}

$success = $error = '';

// ── Fetch courses for dropdown ─────────────────────────────────────
$courses = $conn->query("SELECT id, name FROM courses ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// ── Handle form submission ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $date     = $_POST['att_date'] ?? '';
    $statuses = $_POST['status'] ?? [];   // [user_id => status]

    if (!$courseId || !$date || empty($statuses)) {
        $error = 'Please select a course, date, and mark at least one student.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO attendance (user_id, course_id, date, status)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");
        $stmtTitle = $conn->prepare("SELECT name FROM courses WHERE id = ?");
        $courseName = 'Course';
        if ($stmtTitle) {
            $stmtTitle->bind_param('i', $courseId);
            $stmtTitle->execute();
            $resCourse = $stmtTitle->get_result()->fetch_assoc();
            if ($resCourse) $courseName = $resCourse['name'];
            $stmtTitle->close();
        }

        require_once BASE_PATH . '/includes/notifications_helper.php';

        $savedCount = 0;
        foreach ($statuses as $uid => $status) {
            $uid    = (int) $uid;
            $status = in_array($status, ['present','absent','late','excused']) ? $status : 'absent';
            $stmt->bind_param('iiss', $uid, $courseId, $date, $status);
            $stmt->execute();
            $savedCount++;

            $icon = 'bi-calendar-check-fill';
            $color = 'primary';
            if ($status === 'present') $color = 'success';
            elseif ($status === 'absent') $color = 'danger';
            elseif ($status === 'late') $color = 'warning';
            elseif ($status === 'excused') $color = 'info';
            
            $dateFormatted = date('M d, Y', strtotime($date));
            send_notification(
                $conn, $uid, 'attendance', 
                "Attendance Marked", 
                "You were marked as " . ucfirst($status) . " for {$courseName} on {$dateFormatted}.", 
                $icon, $color, 
                app_url('modules/student/attendance.php')
            );
        }
        $stmt->close();
        $success = "Attendance saved for {$savedCount} students on {$date}.";
    }
}

// ── Load students for selected course ─────────────────────────────
$selectedCourse = (int) ($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
$selectedDate   = $_GET['att_date'] ?? $_POST['att_date'] ?? date('Y-m-d');
$students       = [];
$existing       = [];  // user_id → status (already saved for this date/course)

if ($selectedCourse) {
    // Enrolled students in this course (via users table)
    $stmtS = $conn->prepare("
        SELECT u.id, u.username
        FROM users u
        JOIN enrollments e ON e.user_id = u.id
        WHERE e.course_id = ? AND u.role = 'student'
        ORDER BY u.username ASC
    ");
    $stmtS->bind_param('i', $selectedCourse);
    $stmtS->execute();
    $students = $stmtS->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtS->close();

    // Already saved records for this date
    $stmtE = $conn->prepare("
        SELECT user_id, status FROM attendance
        WHERE course_id = ? AND date = ?
    ");
    $stmtE->bind_param('is', $selectedCourse, $selectedDate);
    $stmtE->execute();
    foreach ($stmtE->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $existing[$r['user_id']] = $r['status'];
    }
    $stmtE->close();
}

require_once BASE_PATH . '/includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Attendance</h2>
        <p class="text-muted small mb-0">Mark daily attendance per course.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success rounded-3 d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success); ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger rounded-3 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4" style="background:var(--app-surface);border:1px solid var(--app-border)!important;border-radius:18px;">
    <div class="card-body p-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Course</label>
                <select name="course_id" class="form-select" onchange="this.form.submit()">
                    <option value="">— Select course —</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id']; ?>" <?= $selectedCourse === (int)$c['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Date</label>
                <input type="date" name="att_date" class="form-control"
                       value="<?= htmlspecialchars($selectedDate); ?>"
                       max="<?= date('Y-m-d'); ?>"
                       onchange="this.form.submit()">
            </div>
        </form>
    </div>
</div>

<!-- Attendance Table -->
<?php if ($selectedCourse && !empty($students)): ?>
<form method="POST">
    <input type="hidden" name="course_id"  value="<?= $selectedCourse; ?>">
    <input type="hidden" name="att_date"   value="<?= htmlspecialchars($selectedDate); ?>">
    <input type="hidden" name="save_attendance" value="1">

    <div class="table-wrapper table-responsive border-0 shadow-sm mb-4"
         style="border-radius:20px;overflow:hidden;background:var(--app-surface);border:1px solid var(--app-border)!important;">
        <table class="table table-hover mb-0 align-middle">
            <thead style="background:var(--app-surface-soft);">
                <tr>
                    <th class="ps-4 py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Student</th>
                    <th class="py-3 text-center" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Present</th>
                    <th class="py-3 text-center" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Absent</th>
                    <th class="py-3 text-center" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Late</th>
                    <th class="pe-4 py-3 text-center" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Excused</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s):
                    $saved = $existing[$s['id']] ?? 'present';
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
                    <?php foreach (['present','absent','late','excused'] as $opt): ?>
                    <td class="text-center" style="border-bottom:1px solid var(--app-border);">
                        <input type="radio"
                               name="status[<?= $s['id']; ?>]"
                               value="<?= $opt; ?>"
                               class="form-check-input"
                               <?= $saved === $opt ? 'checked' : ''; ?>>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-check2-all me-2"></i>Save Attendance
        </button>
    </div>
</form>

<?php elseif ($selectedCourse && empty($students)): ?>
    <div class="card border-0 shadow-sm p-5 text-center" style="background:var(--app-surface);border-radius:20px;">
        <i class="bi bi-people text-muted" style="font-size:3rem;opacity:.5;"></i>
        <h5 class="mt-3">No students enrolled in this course yet.</h5>
        <p class="text-muted">Enroll students via <a href="<?= app_url('modules/admin/courses/enrollments.php'); ?>">Course Enrollments</a>.</p>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm p-5 text-center" style="background:var(--app-surface);border-radius:20px;">
        <i class="bi bi-calendar-check text-muted" style="font-size:3rem;opacity:.5;"></i>
        <h5 class="mt-3">Select a course and date above to begin.</h5>
    </div>
<?php endif; ?>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

