<?php
// modules/admin/courses/create.php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

if (!user_has_role(['admin'])) redirect_to('home.php');

$error = '';
$success = '';

// Fetch all teachers to populate the dropdown
$teachers = $conn->query("SELECT id, name FROM teachers ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $color = trim($_POST['color'] ?? 'primary');

    if (empty($name)) {
        $error = 'Course name is required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO courses (name, teacher_id, color) VALUES (?, ?, ?)");
        $tId = $teacherId > 0 ? $teacherId : null;
        $stmt->bind_param("sis", $name, $tId, $color);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "Course created successfully.";
            redirect_to('modules/admin/courses/index.php');
        } else {
            $error = 'Failed to create course. Please try again.';
        }
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Create Course</h2>
        <p class="text-muted small mb-0">Add a new class and assign a teacher to it.</p>
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

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-semibold">Course Name</label>
                <input type="text" name="name" class="form-control" required placeholder="e.g. Mathematics 101">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Assign Teacher (Optional)</label>
                <select name="teacher_id" class="form-select">
                    <option value="0">— Leave Unassigned —</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id']; ?>"><?= htmlspecialchars($t['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Theme Color</label>
                <select name="color" class="form-select">
                    <option value="primary">Primary (Blue)</option>
                    <option value="success">Success (Green)</option>
                    <option value="danger">Danger (Red)</option>
                    <option value="warning">Warning (Yellow)</option>
                    <option value="info">Info (Light Blue)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary rounded-pill w-100 py-2">
                Create Course
            </button>
        </form>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>


