<?php
// modules/admin/courses/update.php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

if (!user_has_role(['admin'])) redirect_to('home.php');

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect_to('modules/admin/courses/index.php');

$error = '';
$success = '';

// Fetch Course
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) redirect_to('modules/admin/courses/index.php');

// Fetch Teachers
$teachers = $conn->query("SELECT id, name FROM teachers ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $color = trim($_POST['color'] ?? 'primary');

    if (empty($name)) {
        $error = 'Course name is required.';
    } else {
        $stmt = $conn->prepare("UPDATE courses SET name = ?, teacher_id = ?, color = ? WHERE id = ?");
        $tId = $teacherId > 0 ? $teacherId : null;
        $stmt->bind_param("sisi", $name, $tId, $color, $id);
        
        if ($stmt->execute()) {
            $success = "Course updated successfully.";
            $course['name'] = $name;
            $course['teacher_id'] = $tId;
            $course['color'] = $color;
        } else {
            $error = 'Failed to update course.';
        }
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Edit Course</h2>
        <p class="text-muted small mb-0">Update course details or re-assign a teacher.</p>
    </div>
    <a href="<?= app_url('modules/admin/courses/index.php'); ?>" class="btn btn-outline-secondary rounded-pill">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card border-0 shadow-sm" style="max-width: 600px; background: var(--app-surface); border: 1px solid var(--app-border) !important; border-radius: 18px;">
    <div class="card-body p-4">
        <?php if ($error): ?>
            <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success rounded-3"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-semibold">Course Name</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($course['name']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Assign Teacher (Optional)</label>
                <select name="teacher_id" class="form-select">
                    <option value="0">— Leave Unassigned —</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id']; ?>" <?= ($course['teacher_id'] == $t['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($t['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Theme Color</label>
                <select name="color" class="form-select">
                    <option value="primary" <?= $course['color'] === 'primary' ? 'selected' : ''; ?>>Primary (Blue)</option>
                    <option value="success" <?= $course['color'] === 'success' ? 'selected' : ''; ?>>Success (Green)</option>
                    <option value="danger" <?= $course['color'] === 'danger' ? 'selected' : ''; ?>>Danger (Red)</option>
                    <option value="warning" <?= $course['color'] === 'warning' ? 'selected' : ''; ?>>Warning (Yellow)</option>
                    <option value="info" <?= $course['color'] === 'info' ? 'selected' : ''; ?>>Info (Light Blue)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-warning rounded-pill w-100 py-2">
                Save Changes
            </button>
        </form>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>


